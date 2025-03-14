#!/bin/bash
# Backup script for the Documents View feature
# This script handles automated backup of database, document files, and configuration data
# with support for local and cloud storage, compression, and retention management.

set -eo pipefail

# -----------------------------------------------------------------------------
# Global Variables
# -----------------------------------------------------------------------------
SCRIPT_DIR=$(dirname "${BASH_SOURCE[0]}")
REPO_ROOT=$(git rev-parse --show-toplevel)
LOG_FILE="/var/log/documents-view-backup.log"
BACKUP_DIR="/var/backups/documents-view"
DB_BACKUP_DIR="${BACKUP_DIR}/database"
FILE_BACKUP_DIR="${BACKUP_DIR}/documents"
CONFIG_BACKUP_DIR="${BACKUP_DIR}/config"
S3_BUCKET="insurepilot-backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
RETENTION_DAYS="30"
RETENTION_DAYS_MONTHLY="365"
RETENTION_DAYS_WEEKLY="90"
BACKUP_TYPE="incremental"

# -----------------------------------------------------------------------------
# Helper Functions
# -----------------------------------------------------------------------------

# Logs a message to both stdout and the log file
log_message() {
    local level="$1"
    local message="$2"
    local timestamp=$(date +"%Y-%m-%d %H:%M:%S")
    local log_line="[${timestamp}] [${level}] ${message}"
    
    echo "${log_line}"
    echo "${log_line}" >> "${LOG_FILE}"
}

# Checks if required dependencies are installed
check_dependencies() {
    log_message "INFO" "Checking dependencies..."
    local missing_deps=0
    
    # Check for mysqldump (mariadb-client 10.6+)
    if ! command -v mysqldump &> /dev/null; then
        log_message "ERROR" "mysqldump is not installed (required for database backups)"
        missing_deps=1
    fi
    
    # Check for AWS CLI (latest)
    if ! command -v aws &> /dev/null; then
        log_message "WARNING" "AWS CLI is not installed (required for S3 uploads)"
    fi
    
    # Check for rsync (latest)
    if ! command -v rsync &> /dev/null; then
        log_message "ERROR" "rsync is not installed (required for file backups)"
        missing_deps=1
    fi
    
    return $missing_deps
}

# Creates the necessary backup directories if they don't exist
create_backup_directories() {
    log_message "INFO" "Creating backup directories..."
    
    # Create main backup directory
    if ! mkdir -p "${BACKUP_DIR}"; then
        log_message "ERROR" "Failed to create main backup directory: ${BACKUP_DIR}"
        return 1
    fi
    
    # Create database backup directory
    if ! mkdir -p "${DB_BACKUP_DIR}"; then
        log_message "ERROR" "Failed to create database backup directory: ${DB_BACKUP_DIR}"
        return 1
    fi
    
    # Create document files backup directory
    if ! mkdir -p "${FILE_BACKUP_DIR}"; then
        log_message "ERROR" "Failed to create document files backup directory: ${FILE_BACKUP_DIR}"
        return 1
    fi
    
    # Create configuration backup directory
    if ! mkdir -p "${CONFIG_BACKUP_DIR}"; then
        log_message "ERROR" "Failed to create configuration backup directory: ${CONFIG_BACKUP_DIR}"
        return 1
    fi
    
    # Set appropriate permissions (640 for files, 750 for directories)
    chmod 750 "${BACKUP_DIR}" "${DB_BACKUP_DIR}" "${FILE_BACKUP_DIR}" "${CONFIG_BACKUP_DIR}"
    
    log_message "SUCCESS" "Backup directories created successfully"
    return 0
}

# Creates a backup of the database
backup_database() {
    local db_name="$1"
    local db_user="$2"
    local db_password="$3"
    local db_host="$4"
    local backup_type="$5"
    
    log_message "INFO" "Starting ${backup_type} database backup for ${db_name}..."
    
    # Set MYSQL_PWD environment variable to avoid password in command line
    export MYSQL_PWD="${db_password}"
    
    local backup_filename="${DB_BACKUP_DIR}/${db_name}_${backup_type}_${TIMESTAMP}.sql"
    local compressed_backup="${backup_filename}.gz"
    
    if [[ "${backup_type}" == "full" ]]; then
        # Full backup with all tables, routines, triggers, etc.
        if ! mysqldump --user="${db_user}" --host="${db_host}" \
            --single-transaction --routines --triggers --events \
            --databases "${db_name}" > "${backup_filename}"; then
            log_message "ERROR" "Full database backup failed for ${db_name}"
            unset MYSQL_PWD
            return 1
        fi
    elif [[ "${backup_type}" == "incremental" ]]; then
        # Get the binary log position from the latest full backup
        local latest_full_backup=$(ls -t "${DB_BACKUP_DIR}/${db_name}_full_"*.sql.gz 2>/dev/null | head -n1)
        
        if [[ -z "${latest_full_backup}" ]]; then
            log_message "WARNING" "No full backup found, performing full backup instead of incremental"
            backup_type="full"
            backup_filename="${DB_BACKUP_DIR}/${db_name}_full_${TIMESTAMP}.sql"
            
            if ! mysqldump --user="${db_user}" --host="${db_host}" \
                --single-transaction --routines --triggers --events \
                --databases "${db_name}" > "${backup_filename}"; then
                log_message "ERROR" "Full database backup failed for ${db_name}"
                unset MYSQL_PWD
                return 1
            fi
        else
            # Extract binary log position from the latest full backup
            local binlog_file=$(zgrep "CHANGE MASTER TO" "${latest_full_backup}" | grep -oP "MASTER_LOG_FILE='\K[^']+")
            local binlog_pos=$(zgrep "CHANGE MASTER TO" "${latest_full_backup}" | grep -oP "MASTER_LOG_POS=\K[0-9]+")
            
            if [[ -z "${binlog_file}" || -z "${binlog_pos}" ]]; then
                log_message "WARNING" "Could not extract binary log position, performing full backup instead"
                backup_type="full"
                backup_filename="${DB_BACKUP_DIR}/${db_name}_full_${TIMESTAMP}.sql"
                
                if ! mysqldump --user="${db_user}" --host="${db_host}" \
                    --single-transaction --routines --triggers --events \
                    --databases "${db_name}" > "${backup_filename}"; then
                    log_message "ERROR" "Full database backup failed for ${db_name}"
                    unset MYSQL_PWD
                    return 1
                fi
            else
                # Get binary logs from the last position to current
                if ! mysqlbinlog --user="${db_user}" --host="${db_host}" \
                    --start-position="${binlog_pos}" "${binlog_file}" > "${backup_filename}"; then
                    log_message "ERROR" "Incremental database backup failed for ${db_name}"
                    unset MYSQL_PWD
                    return 1
                fi
            fi
        fi
    else
        log_message "ERROR" "Unknown backup type: ${backup_type}"
        unset MYSQL_PWD
        return 1
    fi
    
    # Compress the backup file
    if ! gzip -f "${backup_filename}"; then
        log_message "ERROR" "Failed to compress database backup: ${backup_filename}"
        unset MYSQL_PWD
        return 1
    fi
    
    # Unset the MYSQL_PWD environment variable
    unset MYSQL_PWD
    
    # Verify the backup file exists and has a reasonable size
    if [[ ! -f "${compressed_backup}" || ! -s "${compressed_backup}" ]]; then
        log_message "ERROR" "Backup file does not exist or is empty: ${compressed_backup}"
        return 1
    fi
    
    log_message "SUCCESS" "Database backup completed: ${compressed_backup}"
    echo "${compressed_backup}"
}

# Creates a backup of document files
backup_document_files() {
    local source_dir="$1"
    local backup_type="$2"
    
    log_message "INFO" "Starting ${backup_type} document files backup from ${source_dir}..."
    
    local backup_subdir="${FILE_BACKUP_DIR}/${backup_type}_${TIMESTAMP}"
    local backup_archive="${backup_subdir}.tar.gz"
    
    # Create temporary backup directory
    if ! mkdir -p "${backup_subdir}"; then
        log_message "ERROR" "Failed to create backup directory: ${backup_subdir}"
        return 1
    fi
    
    if [[ "${backup_type}" == "full" ]]; then
        # Full backup of all document files
        if ! rsync -az --delete "${source_dir}/" "${backup_subdir}/"; then
            log_message "ERROR" "Full document files backup failed"
            return 1
        fi
    elif [[ "${backup_type}" == "incremental" ]]; then
        # Find the latest full backup
        local latest_full_backup=$(find "${FILE_BACKUP_DIR}" -name "full_*" -type d | sort -r | head -n1)
        
        if [[ -z "${latest_full_backup}" ]]; then
            log_message "WARNING" "No full backup found, performing full backup instead of incremental"
            
            if ! rsync -az --delete "${source_dir}/" "${backup_subdir}/"; then
                log_message "ERROR" "Full document files backup failed"
                return 1
            fi
        else
            # Incremental backup using the latest full backup as the reference
            if ! rsync -az --delete --link-dest="${latest_full_backup}" "${source_dir}/" "${backup_subdir}/"; then
                log_message "ERROR" "Incremental document files backup failed"
                return 1
            fi
        fi
    else
        log_message "ERROR" "Unknown backup type: ${backup_type}"
        return 1
    fi
    
    # Create a tar archive of the backup directory
    if ! tar -czf "${backup_archive}" -C "${FILE_BACKUP_DIR}" "$(basename "${backup_subdir}")"; then
        log_message "ERROR" "Failed to create tar archive: ${backup_archive}"
        return 1
    fi
    
    # Remove the temporary backup directory
    rm -rf "${backup_subdir}"
    
    # Verify the backup archive exists and has a reasonable size
    if [[ ! -f "${backup_archive}" || ! -s "${backup_archive}" ]]; then
        log_message "ERROR" "Backup archive does not exist or is empty: ${backup_archive}"
        return 1
    fi
    
    log_message "SUCCESS" "Document files backup completed: ${backup_archive}"
    echo "${backup_archive}"
}

# Creates a backup of configuration files
backup_configuration() {
    local config_dir="$1"
    
    log_message "INFO" "Starting configuration backup from ${config_dir}..."
    
    local backup_filename="${CONFIG_BACKUP_DIR}/config_${TIMESTAMP}.tar"
    local compressed_backup="${backup_filename}.gz"
    
    # Create a tar archive of the configuration directory
    if ! tar -cf "${backup_filename}" -C "$(dirname "${config_dir}")" "$(basename "${config_dir}")"; then
        log_message "ERROR" "Failed to create configuration backup: ${backup_filename}"
        return 1
    fi
    
    # Compress the backup file
    if ! gzip -f "${backup_filename}"; then
        log_message "ERROR" "Failed to compress configuration backup: ${backup_filename}"
        return 1
    fi
    
    # Verify the backup file exists and has a reasonable size
    if [[ ! -f "${compressed_backup}" || ! -s "${compressed_backup}" ]]; then
        log_message "ERROR" "Backup file does not exist or is empty: ${compressed_backup}"
        return 1
    fi
    
    log_message "SUCCESS" "Configuration backup completed: ${compressed_backup}"
    echo "${compressed_backup}"
}

# Encrypts a backup file using GPG
encrypt_backup() {
    local file_path="$1"
    local gpg_recipient="$2"
    
    log_message "INFO" "Encrypting backup file: ${file_path}..."
    
    local encrypted_file="${file_path}.gpg"
    
    # Use GPG to encrypt the file
    if ! gpg --batch --yes --recipient "${gpg_recipient}" --output "${encrypted_file}" --encrypt "${file_path}"; then
        log_message "ERROR" "Failed to encrypt backup file: ${file_path}"
        return 1
    fi
    
    # Verify the encrypted file exists and has a reasonable size
    if [[ ! -f "${encrypted_file}" || ! -s "${encrypted_file}" ]]; then
        log_message "ERROR" "Encrypted file does not exist or is empty: ${encrypted_file}"
        return 1
    fi
    
    # Remove the original file after successful encryption
    rm -f "${file_path}"
    
    log_message "SUCCESS" "Backup file encrypted: ${encrypted_file}"
    echo "${encrypted_file}"
}

# Uploads a backup file to S3
upload_to_s3() {
    local file_path="$1"
    local s3_path="$2"
    
    log_message "INFO" "Uploading backup to S3: ${file_path}..."
    
    # Check if AWS CLI is available
    if ! command -v aws &> /dev/null; then
        log_message "ERROR" "AWS CLI is not installed, skipping S3 upload"
        return 1
    fi
    
    # Upload the file to S3
    if ! aws s3 cp "${file_path}" "s3://${S3_BUCKET}/${s3_path}/$(basename "${file_path}")"; then
        log_message "ERROR" "Failed to upload backup to S3: ${file_path}"
        return 1
    fi
    
    log_message "SUCCESS" "Backup uploaded to S3: s3://${S3_BUCKET}/${s3_path}/$(basename "${file_path}")"
    return 0
}

# Verifies the integrity of a backup file
verify_backup() {
    local file_path="$1"
    local backup_type="$2"
    
    log_message "INFO" "Verifying backup integrity: ${file_path}..."
    
    # Check if file exists
    if [[ ! -f "${file_path}" ]]; then
        log_message "ERROR" "Backup file does not exist: ${file_path}"
        return 1
    fi
    
    # Check if file has a reasonable size
    if [[ ! -s "${file_path}" ]]; then
        log_message "ERROR" "Backup file is empty: ${file_path}"
        return 1
    fi
    
    # Perform type-specific verification
    case "${backup_type}" in
        "database")
            # For compressed database dumps, check if the gzip file is valid
            if ! gunzip -t "${file_path}"; then
                log_message "ERROR" "Database backup is corrupted (invalid gzip): ${file_path}"
                return 1
            fi
            
            # Generate a temporary copy for checking
            local temp_file=$(mktemp)
            gunzip -c "${file_path}" > "${temp_file}"
            
            # Use grep to verify if the dump is valid
            if ! grep -q "CREATE TABLE" "${temp_file}" && ! grep -q "INSERT INTO" "${temp_file}"; then
                log_message "ERROR" "Database backup appears to be invalid: ${file_path}"
                rm -f "${temp_file}"
                return 1
            fi
            
            rm -f "${temp_file}"
            ;;
            
        "files")
            # For tar.gz archives, check if the archive is valid
            if ! tar -tzf "${file_path}" &> /dev/null; then
                log_message "ERROR" "Document files backup is corrupted (invalid tar.gz): ${file_path}"
                return 1
            fi
            ;;
            
        "config")
            # For tar.gz archives, check if the archive is valid
            if ! tar -tzf "${file_path}" &> /dev/null; then
                log_message "ERROR" "Configuration backup is corrupted (invalid tar.gz): ${file_path}"
                return 1
            fi
            ;;
            
        *)
            log_message "ERROR" "Unknown backup type for verification: ${backup_type}"
            return 1
            ;;
    esac
    
    # Calculate and store MD5 checksum for future reference
    local md5sum=$(md5sum "${file_path}" | awk '{print $1}')
    echo "${md5sum}" > "${file_path}.md5"
    
    log_message "SUCCESS" "Backup integrity verified: ${file_path} (MD5: ${md5sum})"
    return 0
}

# Removes backups older than the retention period
cleanup_old_backups() {
    local backup_dir="$1"
    local retention_days="$2"
    
    log_message "INFO" "Cleaning up backups older than ${retention_days} days in ${backup_dir}..."
    
    # Find and count files older than the retention period
    local old_files=()
    while IFS= read -r file; do
        if [[ -n "${file}" ]]; then
            old_files+=("${file}")
        fi
    done < <(find "${backup_dir}" -type f -not -path "*.md5" -mtime "+${retention_days}" 2>/dev/null)
    
    local file_count=${#old_files[@]}
    
    if [[ ${file_count} -eq 0 ]]; then
        log_message "INFO" "No backups older than ${retention_days} days found"
        return 0
    fi
    
    # Remove old backup files
    for file in "${old_files[@]}"; do
        log_message "INFO" "Removing old backup: ${file}"
        rm -f "${file}" "${file}.md5" 2>/dev/null
    done
    
    log_message "SUCCESS" "Cleaned up ${file_count} backup files older than ${retention_days} days"
    return 0
}

# Determines the type of backup to perform based on schedule
determine_backup_type() {
    local day_of_week=$(date +%u)  # 1-7, 1 is Monday
    local day_of_month=$(date +%d) # 01-31
    
    # First day of the month: full backup
    if [[ "${day_of_month}" == "01" ]]; then
        echo "full"
        return
    fi
    
    # Sunday (7): full backup
    if [[ "${day_of_week}" == "7" ]]; then
        echo "full"
        return
    fi
    
    # Otherwise: incremental backup
    echo "incremental"
}

# Sends a notification about backup status
send_notification() {
    local status="$1"
    local details="$2"
    
    log_message "INFO" "Sending backup notification (status: ${status})..."
    
    local subject="[InsurePilot] Documents View Backup ${status^^}"
    local message="Backup Status: ${status^^}\n\nDetails:\n${details}\n\nTimestamp: $(date)"
    local recipients="admin@insurepilot.com,ops@insurepilot.com"
    
    # Send email notification
    if command -v mail &> /dev/null; then
        echo -e "${message}" | mail -s "${subject}" "${recipients}"
    else
        log_message "WARNING" "mail command not found, email notification skipped"
    fi
    
    # If status is failure, send additional alert
    if [[ "${status}" == "failure" ]]; then
        # Example: Send to Slack webhook
        if command -v curl &> /dev/null && [[ -n "${SLACK_WEBHOOK_URL}" ]]; then
            local payload="{\"text\":\"${subject}: ${details}\"}"
            curl -s -X POST -H 'Content-type: application/json' --data "${payload}" "${SLACK_WEBHOOK_URL}"
        fi
    fi
    
    log_message "INFO" "Notification sent"
}

# Displays script usage information
show_usage() {
    echo "Usage: $(basename "$0") [OPTIONS]"
    echo "Backup script for the Documents View feature within InsurePilot"
    echo
    echo "Options:"
    echo "  -t, --type TYPE         Backup type (full, incremental, auto)"
    echo "  -d, --database NAME     Database name to backup"
    echo "  -u, --user USER         Database user"
    echo "  -p, --password PASS     Database password"
    echo "  -h, --host HOST         Database host (default: localhost)"
    echo "  -s, --source DIR        Source directory for document files"
    echo "  -c, --config DIR        Configuration directory to backup"
    echo "  -e, --encrypt KEY       Encrypt backups using GPG key ID/email"
    echo "  -b, --s3-bucket BUCKET  S3 bucket for cloud backup (default: ${S3_BUCKET})"
    echo "  -r, --retention DAYS    Retention period in days (default: ${RETENTION_DAYS})"
    echo "  -n, --no-cleanup        Skip cleanup of old backups"
    echo "  --no-s3                 Skip uploading to S3"
    echo "  --help                  Display this help message"
    echo
    echo "Examples:"
    echo "  $(basename "$0") --type full --database documents-view --user dbuser --password dbpass --source /var/www/documents"
    echo "  $(basename "$0") --type incremental --database documents-view --encrypt admin@insurepilot.com"
}

# Main function that orchestrates the backup process
main() {
    local db_name=""
    local db_user=""
    local db_password=""
    local db_host="localhost"
    local source_dir=""
    local config_dir=""
    local gpg_recipient=""
    local no_cleanup=0
    local no_s3=0
    local do_db_backup=0
    local do_file_backup=0
    local do_config_backup=0
    local exit_code=0
    local backup_status="success"
    local backup_details=""
    
    # Parse command line arguments
    while [[ $# -gt 0 ]]; do
        case "$1" in
            -t|--type)
                BACKUP_TYPE="$2"
                shift 2
                ;;
            -d|--database)
                db_name="$2"
                do_db_backup=1
                shift 2
                ;;
            -u|--user)
                db_user="$2"
                shift 2
                ;;
            -p|--password)
                db_password="$2"
                shift 2
                ;;
            -h|--host)
                db_host="$2"
                shift 2
                ;;
            -s|--source)
                source_dir="$2"
                do_file_backup=1
                shift 2
                ;;
            -c|--config)
                config_dir="$2"
                do_config_backup=1
                shift 2
                ;;
            -e|--encrypt)
                gpg_recipient="$2"
                shift 2
                ;;
            -b|--s3-bucket)
                S3_BUCKET="$2"
                shift 2
                ;;
            -r|--retention)
                RETENTION_DAYS="$2"
                shift 2
                ;;
            -n|--no-cleanup)
                no_cleanup=1
                shift
                ;;
            --no-s3)
                no_s3=1
                shift
                ;;
            --help)
                show_usage
                exit 0
                ;;
            *)
                log_message "ERROR" "Unknown option: $1"
                show_usage
                exit 1
                ;;
        esac
    done
    
    # Initialize log file if it doesn't exist
    if [[ ! -f "${LOG_FILE}" ]]; then
        touch "${LOG_FILE}"
        chmod 640 "${LOG_FILE}"
    fi
    
    log_message "INFO" "Starting backup process (type: ${BACKUP_TYPE})..."
    
    # Check dependencies
    if ! check_dependencies; then
        log_message "ERROR" "Missing required dependencies, aborting backup"
        backup_status="failure"
        backup_details="Missing required dependencies"
        send_notification "${backup_status}" "${backup_details}"
        return 1
    fi
    
    # Create backup directories
    if ! create_backup_directories; then
        log_message "ERROR" "Failed to create backup directories, aborting backup"
        backup_status="failure"
        backup_details="Failed to create backup directories"
        send_notification "${backup_status}" "${backup_details}"
        return 1
    fi
    
    # Determine backup type if set to auto
    if [[ "${BACKUP_TYPE}" == "auto" ]]; then
        BACKUP_TYPE=$(determine_backup_type)
        log_message "INFO" "Auto backup type determined: ${BACKUP_TYPE}"
    fi
    
    # Validate backup type
    if [[ "${BACKUP_TYPE}" != "full" && "${BACKUP_TYPE}" != "incremental" ]]; then
        log_message "ERROR" "Invalid backup type: ${BACKUP_TYPE}"
        backup_status="failure"
        backup_details="Invalid backup type: ${BACKUP_TYPE}"
        send_notification "${backup_status}" "${backup_details}"
        return 1
    fi
    
    # Array to store all backup files
    local backup_files=()
    local backup_types=()
    
    # Perform database backup if requested
    if [[ ${do_db_backup} -eq 1 ]]; then
        if [[ -z "${db_name}" || -z "${db_user}" || -z "${db_password}" ]]; then
            log_message "ERROR" "Database name, user, and password are required for database backup"
            backup_status="failure"
            backup_details="Missing database parameters"
            send_notification "${backup_status}" "${backup_details}"
            return 1
        fi
        
        local db_backup_file
        if ! db_backup_file=$(backup_database "${db_name}" "${db_user}" "${db_password}" "${db_host}" "${BACKUP_TYPE}"); then
            log_message "ERROR" "Database backup failed"
            exit_code=1
            backup_status="failure"
            backup_details="Database backup failed"
        else
            backup_files+=("${db_backup_file}")
            backup_types+=("database")
            backup_details="${backup_details}Database backup successful: $(basename "${db_backup_file}")\n"
        fi
    fi
    
    # Perform document files backup if requested
    if [[ ${do_file_backup} -eq 1 ]]; then
        if [[ -z "${source_dir}" ]]; then
            log_message "ERROR" "Source directory is required for document files backup"
            backup_status="failure"
            backup_details="${backup_details}Missing source directory\n"
            if [[ ${exit_code} -eq 0 ]]; then
                exit_code=1
            fi
        elif [[ ! -d "${source_dir}" ]]; then
            log_message "ERROR" "Source directory does not exist: ${source_dir}"
            backup_status="failure"
            backup_details="${backup_details}Source directory does not exist: ${source_dir}\n"
            if [[ ${exit_code} -eq 0 ]]; then
                exit_code=1
            fi
        else
            local file_backup_file
            if ! file_backup_file=$(backup_document_files "${source_dir}" "${BACKUP_TYPE}"); then
                log_message "ERROR" "Document files backup failed"
                if [[ ${exit_code} -eq 0 ]]; then
                    exit_code=1
                fi
                backup_status="failure"
                backup_details="${backup_details}Document files backup failed\n"
            else
                backup_files+=("${file_backup_file}")
                backup_types+=("files")
                backup_details="${backup_details}Document files backup successful: $(basename "${file_backup_file}")\n"
            fi
        fi
    fi
    
    # Perform configuration backup if requested
    if [[ ${do_config_backup} -eq 1 ]]; then
        if [[ -z "${config_dir}" ]]; then
            log_message "ERROR" "Configuration directory is required for configuration backup"
            backup_status="failure"
            backup_details="${backup_details}Missing configuration directory\n"
            if [[ ${exit_code} -eq 0 ]]; then
                exit_code=1
            fi
        elif [[ ! -d "${config_dir}" ]]; then
            log_message "ERROR" "Configuration directory does not exist: ${config_dir}"
            backup_status="failure"
            backup_details="${backup_details}Configuration directory does not exist: ${config_dir}\n"
            if [[ ${exit_code} -eq 0 ]]; then
                exit_code=1
            fi
        else
            local config_backup_file
            if ! config_backup_file=$(backup_configuration "${config_dir}"); then
                log_message "ERROR" "Configuration backup failed"
                if [[ ${exit_code} -eq 0 ]]; then
                    exit_code=1
                fi
                backup_status="failure"
                backup_details="${backup_details}Configuration backup failed\n"
            else
                backup_files+=("${config_backup_file}")
                backup_types+=("config")
                backup_details="${backup_details}Configuration backup successful: $(basename "${config_backup_file}")\n"
            fi
        fi
    fi
    
    # If no backups were performed, exit
    if [[ ${#backup_files[@]} -eq 0 ]]; then
        log_message "ERROR" "No backups were performed"
        backup_status="failure"
        backup_details="No backups were performed"
        send_notification "${backup_status}" "${backup_details}"
        return 1
    fi
    
    # Encrypt backups if requested
    if [[ -n "${gpg_recipient}" ]]; then
        # Make sure gpg is installed
        if ! command -v gpg &> /dev/null; then
            log_message "ERROR" "GPG is not installed, skipping encryption"
            backup_status="warning"
            backup_details="${backup_details}GPG not installed, encryption skipped\n"
        else
            local encrypted_files=()
            for i in "${!backup_files[@]}"; do
                local encrypted_file
                if ! encrypted_file=$(encrypt_backup "${backup_files[$i]}" "${gpg_recipient}"); then
                    log_message "ERROR" "Failed to encrypt backup: ${backup_files[$i]}"
                    if [[ ${exit_code} -eq 0 ]]; then
                        exit_code=1
                    fi
                    backup_status="warning"
                    backup_details="${backup_details}Encryption failed for: $(basename "${backup_files[$i]}")\n"
                else
                    encrypted_files+=("${encrypted_file}")
                    backup_details="${backup_details}Encryption successful: $(basename "${encrypted_file}")\n"
                fi
            done
            
            # Replace original files with encrypted ones
            if [[ ${#encrypted_files[@]} -gt 0 ]]; then
                backup_files=("${encrypted_files[@]}")
            fi
        fi
    fi
    
    # Upload backups to S3 if not disabled
    if [[ ${no_s3} -eq 0 ]]; then
        for i in "${!backup_files[@]}"; do
            local s3_path="${BACKUP_TYPE}"
            
            if [[ "${backup_types[$i]}" == "database" ]]; then
                s3_path="database/${s3_path}"
            elif [[ "${backup_types[$i]}" == "files" ]]; then
                s3_path="documents/${s3_path}"
            elif [[ "${backup_types[$i]}" == "config" ]]; then
                s3_path="config"
            fi
            
            if ! upload_to_s3 "${backup_files[$i]}" "${s3_path}"; then
                log_message "WARNING" "Failed to upload backup to S3: ${backup_files[$i]}"
                backup_status="warning"
                backup_details="${backup_details}S3 upload failed for: $(basename "${backup_files[$i]}")\n"
            else
                backup_details="${backup_details}S3 upload successful: $(basename "${backup_files[$i]}")\n"
            fi
        done
    fi
    
    # Verify backup integrity
    for i in "${!backup_files[@]}"; do
        if ! verify_backup "${backup_files[$i]}" "${backup_types[$i]}"; then
            log_message "ERROR" "Backup verification failed: ${backup_files[$i]}"
            if [[ ${exit_code} -eq 0 ]]; then
                exit_code=1
            fi
            backup_status="failure"
            backup_details="${backup_details}Verification failed for: $(basename "${backup_files[$i]}")\n"
        else
            backup_details="${backup_details}Verification successful: $(basename "${backup_files[$i]}")\n"
        fi
    done
    
    # Clean up old backups if not disabled
    if [[ ${no_cleanup} -eq 0 ]]; then
        # Determine appropriate retention based on backup type
        local retention="${RETENTION_DAYS}"
        
        if [[ "${BACKUP_TYPE}" == "full" ]]; then
            # For monthly full backups (1st of month)
            if [[ "$(date +%d)" == "01" ]]; then
                retention="${RETENTION_DAYS_MONTHLY}"
            # For weekly full backups (Sunday)
            elif [[ "$(date +%u)" == "7" ]]; then
                retention="${RETENTION_DAYS_WEEKLY}"
            fi
        fi
        
        if ! cleanup_old_backups "${DB_BACKUP_DIR}" "${retention}"; then
            log_message "WARNING" "Failed to clean up old database backups"
            backup_status="warning"
            backup_details="${backup_details}Cleanup failed for database backups\n"
        fi
        
        if ! cleanup_old_backups "${FILE_BACKUP_DIR}" "${retention}"; then
            log_message "WARNING" "Failed to clean up old document files backups"
            backup_status="warning"
            backup_details="${backup_details}Cleanup failed for document files backups\n"
        fi
        
        if ! cleanup_old_backups "${CONFIG_BACKUP_DIR}" "${retention}"; then
            log_message "WARNING" "Failed to clean up old configuration backups"
            backup_status="warning"
            backup_details="${backup_details}Cleanup failed for configuration backups\n"
        fi
    fi
    
    # Send notification about backup status
    send_notification "${backup_status}" "${backup_details}"
    
    if [[ ${exit_code} -eq 0 ]]; then
        log_message "SUCCESS" "Backup process completed successfully"
    else
        log_message "ERROR" "Backup process completed with errors"
    fi
    
    return ${exit_code}
}

# Execute the main function with all arguments
main "$@"