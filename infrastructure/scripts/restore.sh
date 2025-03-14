#!/bin/bash
# Restoration script for the Documents View feature
# This script handles automated recovery of database, document files, and configuration data from backups

set -eo pipefail

# -----------------------------------------------------------------------------
# Global Variables
# -----------------------------------------------------------------------------
SCRIPT_DIR=$(dirname "${BASH_SOURCE[0]}")
REPO_ROOT=$(git rev-parse --show-toplevel)
LOG_FILE="/var/log/documents-view-restore.log"
BACKUP_DIR="/var/backups/documents-view"
DB_BACKUP_DIR="${BACKUP_DIR}/database"
FILE_BACKUP_DIR="${BACKUP_DIR}/documents"
CONFIG_BACKUP_DIR="${BACKUP_DIR}/config"
S3_BUCKET="insurepilot-backups"
RESTORE_DIR="/tmp/documents-view-restore"
DB_RESTORE_DIR="${RESTORE_DIR}/database"
FILE_RESTORE_DIR="${RESTORE_DIR}/documents"
CONFIG_RESTORE_DIR="${RESTORE_DIR}/config"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")

# Import the verify_backup function from backup.sh
source "${SCRIPT_DIR}/backup.sh"

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
    
    # Check for mysql (mariadb-client 10.6+)
    if ! command -v mysql &> /dev/null; then
        log_message "ERROR" "mysql is not installed (required for database restoration)"
        missing_deps=1
    fi
    
    # Check for AWS CLI (latest)
    if ! command -v aws &> /dev/null; then
        log_message "WARNING" "AWS CLI is not installed (required for S3 downloads)"
    fi
    
    # Check for rsync (latest)
    if ! command -v rsync &> /dev/null; then
        log_message "ERROR" "rsync is not installed (required for file restoration)"
        missing_deps=1
    fi
    
    # Check for tar and gzip
    if ! command -v tar &> /dev/null || ! command -v gzip &> /dev/null; then
        log_message "ERROR" "tar or gzip is not installed (required for archive handling)"
        missing_deps=1
    fi
    
    return $missing_deps
}

# Creates the necessary temporary restoration directories
create_restore_directories() {
    log_message "INFO" "Creating restoration directories..."
    
    # Create main restore directory
    if ! mkdir -p "${RESTORE_DIR}"; then
        log_message "ERROR" "Failed to create main restore directory: ${RESTORE_DIR}"
        return 1
    fi
    
    # Create database restore directory
    if ! mkdir -p "${DB_RESTORE_DIR}"; then
        log_message "ERROR" "Failed to create database restore directory: ${DB_RESTORE_DIR}"
        return 1
    fi
    
    # Create document files restore directory
    if ! mkdir -p "${FILE_RESTORE_DIR}"; then
        log_message "ERROR" "Failed to create document files restore directory: ${FILE_RESTORE_DIR}"
        return 1
    fi
    
    # Create configuration restore directory
    if ! mkdir -p "${CONFIG_RESTORE_DIR}"; then
        log_message "ERROR" "Failed to create configuration restore directory: ${CONFIG_RESTORE_DIR}"
        return 1
    fi
    
    # Set appropriate permissions
    chmod 750 "${RESTORE_DIR}" "${DB_RESTORE_DIR}" "${FILE_RESTORE_DIR}" "${CONFIG_RESTORE_DIR}"
    
    log_message "SUCCESS" "Restoration directories created successfully"
    return 0
}

# Downloads a backup file from S3
download_from_s3() {
    local s3_path="$1"
    local local_path="$2"
    
    log_message "INFO" "Downloading backup from S3: s3://${S3_BUCKET}/${s3_path}..."
    
    # Create directory structure if it doesn't exist
    mkdir -p "$(dirname "${local_path}")"
    
    # Download the file from S3
    if ! aws s3 cp "s3://${S3_BUCKET}/${s3_path}" "${local_path}"; then
        log_message "ERROR" "Failed to download backup from S3: ${s3_path}"
        return 1
    fi
    
    # Verify the file was downloaded successfully
    if [[ ! -f "${local_path}" || ! -s "${local_path}" ]]; then
        log_message "ERROR" "Downloaded file is empty or does not exist: ${local_path}"
        return 1
    fi
    
    log_message "SUCCESS" "Backup downloaded from S3: ${local_path}"
    return 0
}

# Decrypts an encrypted backup file
decrypt_backup() {
    local file_path="$1"
    local gpg_key_id="$2"
    
    log_message "INFO" "Decrypting backup file: ${file_path}..."
    
    local decrypted_file="${file_path%.gpg}"
    
    # Check if GPG is installed
    if ! command -v gpg &> /dev/null; then
        log_message "ERROR" "GPG is not installed (required for decryption)"
        return 1
    fi
    
    # Decrypt the file
    if ! gpg --batch --quiet --yes --recipient "${gpg_key_id}" --output "${decrypted_file}" --decrypt "${file_path}"; then
        log_message "ERROR" "Failed to decrypt backup file: ${file_path}"
        return 1
    fi
    
    # Verify the decrypted file exists and has a reasonable size
    if [[ ! -f "${decrypted_file}" || ! -s "${decrypted_file}" ]]; then
        log_message "ERROR" "Decrypted file is empty or does not exist: ${decrypted_file}"
        return 1
    fi
    
    log_message "SUCCESS" "Backup file decrypted: ${decrypted_file}"
    echo "${decrypted_file}"
}

# Lists available backups for a specific type
list_available_backups() {
    local backup_type="$1"
    local source="$2"
    local pattern="$3"
    
    log_message "INFO" "Listing available ${backup_type} backups from ${source}..."
    
    local backup_list=()
    
    if [[ "${source}" == "local" ]]; then
        # Get local backup directory path based on type
        local backup_dir=""
        case "${backup_type}" in
            "database")
                backup_dir="${DB_BACKUP_DIR}"
                ;;
            "files")
                backup_dir="${FILE_BACKUP_DIR}"
                ;;
            "config")
                backup_dir="${CONFIG_BACKUP_DIR}"
                ;;
            *)
                log_message "ERROR" "Unknown backup type: ${backup_type}"
                return 1
                ;;
        esac
        
        # Find matching backup files in the local directory
        while IFS= read -r file; do
            if [[ -n "${file}" ]]; then
                backup_list+=("${file}")
            fi
        done < <(find "${backup_dir}" -type f -name "${pattern}" 2>/dev/null | sort -r)
    elif [[ "${source}" == "s3" ]]; then
        # Determine S3 prefix based on backup type
        local s3_prefix=""
        case "${backup_type}" in
            "database")
                s3_prefix="database"
                ;;
            "files")
                s3_prefix="documents"
                ;;
            "config")
                s3_prefix="config"
                ;;
            *)
                log_message "ERROR" "Unknown backup type: ${backup_type}"
                return 1
                ;;
        esac
        
        # List objects in the S3 bucket with the specified prefix and pattern
        while IFS= read -r file; do
            if [[ -n "${file}" ]]; then
                backup_list+=("${file}")
            fi
        done < <(aws s3 ls "s3://${S3_BUCKET}/${s3_prefix}/" | grep "${pattern}" | awk '{print $4}' | sort -r)
    else
        log_message "ERROR" "Unknown backup source: ${source}"
        return 1
    fi
    
    # Check if any backups were found
    if [[ ${#backup_list[@]} -eq 0 ]]; then
        log_message "WARNING" "No ${backup_type} backups found matching pattern ${pattern}"
        return 1
    fi
    
    log_message "INFO" "Found ${#backup_list[@]} ${backup_type} backups"
    
    # Print the list of backups
    for backup in "${backup_list[@]}"; do
        echo "${backup}"
    done
}

# Restores the database from a backup file
restore_database() {
    local backup_file="$1"
    local db_name="$2"
    local db_user="$3"
    local db_password="$4"
    local db_host="$5"
    
    log_message "INFO" "Restoring database ${db_name} from ${backup_file}..."
    
    # Check if the backup file exists
    if [[ ! -f "${backup_file}" ]]; then
        log_message "ERROR" "Backup file does not exist: ${backup_file}"
        return 1
    fi
    
    # Determine if this is a full or incremental backup based on the filename
    local backup_type="full"
    if [[ "${backup_file}" == *"incremental"* ]]; then
        backup_type="incremental"
    fi
    
    # Set MYSQL_PWD environment variable to avoid password in command line
    export MYSQL_PWD="${db_password}"
    
    # Decompress backup file if needed
    local uncompressed_file=""
    if [[ "${backup_file}" == *.gz ]]; then
        uncompressed_file="${DB_RESTORE_DIR}/$(basename "${backup_file}" .gz)"
        log_message "INFO" "Decompressing backup file..."
        if ! gunzip -c "${backup_file}" > "${uncompressed_file}"; then
            log_message "ERROR" "Failed to decompress backup file: ${backup_file}"
            unset MYSQL_PWD
            return 1
        fi
    else
        uncompressed_file="${backup_file}"
    fi
    
    # Restore based on backup type
    if [[ "${backup_type}" == "full" ]]; then
        log_message "INFO" "Performing full database restoration..."
        
        # Create/reset the database
        if ! mysql --user="${db_user}" --host="${db_host}" -e "DROP DATABASE IF EXISTS ${db_name}; CREATE DATABASE ${db_name};"; then
            log_message "ERROR" "Failed to create/reset database: ${db_name}"
            unset MYSQL_PWD
            return 1
        fi
        
        # Restore the full backup
        if ! mysql --user="${db_user}" --host="${db_host}" "${db_name}" < "${uncompressed_file}"; then
            log_message "ERROR" "Failed to restore database from backup: ${backup_file}"
            unset MYSQL_PWD
            return 1
        fi
    elif [[ "${backup_type}" == "incremental" ]]; then
        log_message "INFO" "Performing incremental database restoration..."
        
        # For incremental restore, we need to find and apply the latest full backup first,
        # then apply incremental backups in chronological order
        
        # Extract timestamp from the backup filename
        local backup_timestamp=$(basename "${backup_file}" | grep -oP '\d{8}_\d{6}')
        
        # Find the latest full backup before this incremental backup
        local full_backup=""
        for f in $(find "${DB_BACKUP_DIR}" -name "*full*.sql.gz" | sort -r); do
            local full_timestamp=$(basename "${f}" | grep -oP '\d{8}_\d{6}')
            if [[ "${full_timestamp}" < "${backup_timestamp}" ]]; then
                full_backup="${f}"
                break
            fi
        done
        
        if [[ -z "${full_backup}" ]]; then
            log_message "ERROR" "No suitable full backup found for incremental restore"
            unset MYSQL_PWD
            return 1
        fi
        
        # Create a temporary database for restoration
        local temp_db="${db_name}_temp"
        
        log_message "INFO" "Creating temporary database ${temp_db} for incremental restore..."
        if ! mysql --user="${db_user}" --host="${db_host}" -e "DROP DATABASE IF EXISTS ${temp_db}; CREATE DATABASE ${temp_db};"; then
            log_message "ERROR" "Failed to create temporary database: ${temp_db}"
            unset MYSQL_PWD
            return 1
        fi
        
        # Decompress and restore the full backup
        local uncompressed_full="${DB_RESTORE_DIR}/$(basename "${full_backup}" .gz)"
        if ! gunzip -c "${full_backup}" > "${uncompressed_full}"; then
            log_message "ERROR" "Failed to decompress full backup: ${full_backup}"
            unset MYSQL_PWD
            return 1
        fi
        
        log_message "INFO" "Restoring full backup to temporary database..."
        if ! mysql --user="${db_user}" --host="${db_host}" "${temp_db}" < "${uncompressed_full}"; then
            log_message "ERROR" "Failed to restore full backup to temporary database"
            unset MYSQL_PWD
            return 1
        fi
        
        # Apply the incremental backup
        log_message "INFO" "Applying incremental backup to temporary database..."
        if ! mysql --user="${db_user}" --host="${db_host}" "${temp_db}" < "${uncompressed_file}"; then
            log_message "ERROR" "Failed to apply incremental backup to temporary database"
            unset MYSQL_PWD
            return 1
        fi
        
        # Replace the original database with the temporary one
        log_message "INFO" "Replacing original database with restored database..."
        if ! mysql --user="${db_user}" --host="${db_host}" -e "DROP DATABASE IF EXISTS ${db_name}; CREATE DATABASE ${db_name};"; then
            log_message "ERROR" "Failed to reset original database: ${db_name}"
            unset MYSQL_PWD
            return 1
        fi
        
        # Dump and restore the temporary database to the original
        log_message "INFO" "Transferring data from temporary database to original..."
        if ! mysqldump --user="${db_user}" --host="${db_host}" "${temp_db}" | mysql --user="${db_user}" --host="${db_host}" "${db_name}"; then
            log_message "ERROR" "Failed to transfer data from temporary database to original"
            unset MYSQL_PWD
            return 1
        fi
        
        # Drop the temporary database
        log_message "INFO" "Dropping temporary database..."
        if ! mysql --user="${db_user}" --host="${db_host}" -e "DROP DATABASE IF EXISTS ${temp_db};"; then
            log_message "WARNING" "Failed to drop temporary database: ${temp_db}"
        fi
        
        # Clean up temporary files
        rm -f "${uncompressed_full}"
    else
        log_message "ERROR" "Unknown backup type: ${backup_type}"
        unset MYSQL_PWD
        return 1
    fi
    
    # Clean up temporary files if we decompressed
    if [[ "${backup_file}" == *.gz && -n "${uncompressed_file}" ]]; then
        rm -f "${uncompressed_file}"
    fi
    
    # Unset the MYSQL_PWD environment variable
    unset MYSQL_PWD
    
    log_message "SUCCESS" "Database ${db_name} restored successfully from ${backup_file}"
    return 0
}

# Restores document files from a backup archive
restore_document_files() {
    local backup_file="$1"
    local target_dir="$2"
    
    log_message "INFO" "Restoring document files to ${target_dir} from ${backup_file}..."
    
    # Check if the backup file exists
    if [[ ! -f "${backup_file}" ]]; then
        log_message "ERROR" "Backup file does not exist: ${backup_file}"
        return 1
    fi
    
    # Check if the target directory exists, create if not
    if [[ ! -d "${target_dir}" ]]; then
        log_message "INFO" "Target directory does not exist, creating: ${target_dir}"
        if ! mkdir -p "${target_dir}"; then
            log_message "ERROR" "Failed to create target directory: ${target_dir}"
            return 1
        fi
    fi
    
    # Create a temporary extraction directory
    local extract_dir="${FILE_RESTORE_DIR}/extract_${TIMESTAMP}"
    if ! mkdir -p "${extract_dir}"; then
        log_message "ERROR" "Failed to create extraction directory: ${extract_dir}"
        return 1
    fi
    
    # Extract the backup archive
    log_message "INFO" "Extracting backup archive..."
    if ! tar -xzf "${backup_file}" -C "${extract_dir}"; then
        log_message "ERROR" "Failed to extract backup archive: ${backup_file}"
        rm -rf "${extract_dir}"
        return 1
    fi
    
    # Find the extracted directory (should be a single directory with the timestamp)
    local extracted_dir=""
    extracted_dir=$(find "${extract_dir}" -mindepth 1 -maxdepth 1 -type d | head -n1)
    
    if [[ -z "${extracted_dir}" ]]; then
        log_message "ERROR" "No directory found in extracted backup"
        rm -rf "${extract_dir}"
        return 1
    fi
    
    # Use rsync to copy files from the extraction directory to the target directory
    log_message "INFO" "Copying files to target directory..."
    if ! rsync -a --delete "${extracted_dir}/" "${target_dir}/"; then
        log_message "ERROR" "Failed to copy files to target directory: ${target_dir}"
        rm -rf "${extract_dir}"
        return 1
    fi
    
    # Set appropriate permissions on the target directory
    log_message "INFO" "Setting permissions on target directory..."
    chmod -R 750 "${target_dir}"
    
    # Clean up the extraction directory
    rm -rf "${extract_dir}"
    
    log_message "SUCCESS" "Document files restored successfully to ${target_dir}"
    return 0
}

# Restores configuration files from a backup archive
restore_configuration() {
    local backup_file="$1"
    local target_dir="$2"
    
    log_message "INFO" "Restoring configuration to ${target_dir} from ${backup_file}..."
    
    # Check if the backup file exists
    if [[ ! -f "${backup_file}" ]]; then
        log_message "ERROR" "Backup file does not exist: ${backup_file}"
        return 1
    fi
    
    # Check if the target directory exists, create if not
    if [[ ! -d "${target_dir}" ]]; then
        log_message "INFO" "Target directory does not exist, creating: ${target_dir}"
        if ! mkdir -p "${target_dir}"; then
            log_message "ERROR" "Failed to create target directory: ${target_dir}"
            return 1
        fi
    fi
    
    # Create a temporary extraction directory
    local extract_dir="${CONFIG_RESTORE_DIR}/extract_${TIMESTAMP}"
    if ! mkdir -p "${extract_dir}"; then
        log_message "ERROR" "Failed to create extraction directory: ${extract_dir}"
        return 1
    fi
    
    # Extract the backup archive
    log_message "INFO" "Extracting backup archive..."
    if ! tar -xzf "${backup_file}" -C "${extract_dir}"; then
        log_message "ERROR" "Failed to extract backup archive: ${backup_file}"
        rm -rf "${extract_dir}"
        return 1
    fi
    
    # Find the extracted directory (should contain the configuration files)
    local config_dir_name=$(basename "${target_dir}")
    local extracted_config="${extract_dir}/${config_dir_name}"
    
    if [[ ! -d "${extracted_config}" ]]; then
        log_message "ERROR" "Configuration directory not found in extracted backup: ${config_dir_name}"
        rm -rf "${extract_dir}"
        return 1
    fi
    
    # Use rsync to copy files from the extraction directory to the target directory
    log_message "INFO" "Copying configuration files to target directory..."
    if ! rsync -a --delete "${extracted_config}/" "${target_dir}/"; then
        log_message "ERROR" "Failed to copy configuration files to target directory: ${target_dir}"
        rm -rf "${extract_dir}"
        return 1
    fi
    
    # Set appropriate permissions on the target directory
    log_message "INFO" "Setting permissions on target directory..."
    chmod -R 750 "${target_dir}"
    
    # Clean up the extraction directory
    rm -rf "${extract_dir}"
    
    log_message "SUCCESS" "Configuration restored successfully to ${target_dir}"
    return 0
}

# Verifies the restoration was successful
verify_restoration() {
    local restore_type="$1"
    local target="$2"
    
    log_message "INFO" "Verifying ${restore_type} restoration to ${target}..."
    
    case "${restore_type}" in
        "database")
            local db_name="${target}"
            local db_user="$3"
            local db_password="$4"
            local db_host="$5"
            
            # Set MYSQL_PWD environment variable to avoid password in command line
            export MYSQL_PWD="${db_password}"
            
            # Check if we can connect to the database
            if ! mysql --user="${db_user}" --host="${db_host}" -e "USE ${db_name};" &> /dev/null; then
                log_message "ERROR" "Cannot connect to restored database: ${db_name}"
                unset MYSQL_PWD
                return 1
            fi
            
            # Check if we can run basic queries
            if ! mysql --user="${db_user}" --host="${db_host}" -e "SHOW TABLES FROM ${db_name};" &> /dev/null; then
                log_message "ERROR" "Cannot run basic queries on restored database: ${db_name}"
                unset MYSQL_PWD
                return 1
            fi
            
            # Check if key tables exist
            local required_tables=("document" "map_document_file" "map_document_action" "file" "action")
            for table in "${required_tables[@]}"; do
                if ! mysql --user="${db_user}" --host="${db_host}" -e "SELECT 1 FROM ${db_name}.${table} LIMIT 1;" &> /dev/null; then
                    log_message "WARNING" "Key table may be missing or empty: ${table}"
                fi
            done
            
            # Unset the MYSQL_PWD environment variable
            unset MYSQL_PWD
            ;;
            
        "files")
            # Check if the target directory exists
            if [[ ! -d "${target}" ]]; then
                log_message "ERROR" "Target directory does not exist: ${target}"
                return 1
            fi
            
            # Check if the directory contains files
            if [[ -z "$(ls -A "${target}" 2>/dev/null)" ]]; then
                log_message "ERROR" "Target directory is empty: ${target}"
                return 1
            fi
            
            # Check permissions
            if [[ "$(stat -c "%a" "${target}")" != "750" ]]; then
                log_message "WARNING" "Target directory has incorrect permissions: $(stat -c "%a" "${target}")"
            fi
            
            # Check a sample of files for readability
            local file_count=0
            local problem_files=0
            
            while IFS= read -r file; do
                ((file_count++))
                if [[ ! -r "${file}" ]]; then
                    log_message "WARNING" "File is not readable: ${file}"
                    ((problem_files++))
                fi
            done < <(find "${target}" -type f -name "*.pdf" | head -n 10)
            
            if [[ ${file_count} -eq 0 ]]; then
                log_message "WARNING" "No PDF files found in target directory"
            elif [[ ${problem_files} -gt 0 ]]; then
                log_message "WARNING" "${problem_files} out of ${file_count} sampled files have problems"
            fi
            ;;
            
        "config")
            # Check if the target directory exists
            if [[ ! -d "${target}" ]]; then
                log_message "ERROR" "Target directory does not exist: ${target}"
                return 1
            fi
            
            # Check if the directory contains files
            if [[ -z "$(ls -A "${target}" 2>/dev/null)" ]]; then
                log_message "ERROR" "Target directory is empty: ${target}"
                return 1
            fi
            
            # Check permissions
            if [[ "$(stat -c "%a" "${target}")" != "750" ]]; then
                log_message "WARNING" "Target directory has incorrect permissions: $(stat -c "%a" "${target}")"
            fi
            
            # Check key configuration files
            local required_files=(".env" "config/database.php" "config/app.php")
            for file in "${required_files[@]}"; do
                if [[ ! -f "${target}/${file}" ]]; then
                    log_message "WARNING" "Key configuration file is missing: ${file}"
                elif [[ ! -r "${target}/${file}" ]]; then
                    log_message "WARNING" "Key configuration file is not readable: ${file}"
                fi
            done
            ;;
            
        *)
            log_message "ERROR" "Unknown restoration type: ${restore_type}"
            return 1
            ;;
    esac
    
    log_message "SUCCESS" "${restore_type} restoration verified successfully"
    return 0
}

# Performs a point-in-time recovery for the database
perform_point_in_time_recovery() {
    local base_backup_file="$1"
    local binary_logs_dir="$2"
    local target_timestamp="$3"
    local db_name="$4"
    local db_user="$5"
    local db_password="$6"
    local db_host="$7"
    
    log_message "INFO" "Performing point-in-time recovery for database ${db_name} to ${target_timestamp}..."
    
    # Check if the base backup file exists
    if [[ ! -f "${base_backup_file}" ]]; then
        log_message "ERROR" "Base backup file does not exist: ${base_backup_file}"
        return 1
    fi
    
    # Check if the binary logs directory exists
    if [[ ! -d "${binary_logs_dir}" ]]; then
        log_message "ERROR" "Binary logs directory does not exist: ${binary_logs_dir}"
        return 1
    fi
    
    # Create a temporary database for restoration
    local temp_db="${db_name}_pitr"
    
    # Set MYSQL_PWD environment variable to avoid password in command line
    export MYSQL_PWD="${db_password}"
    
    log_message "INFO" "Creating temporary database ${temp_db} for point-in-time recovery..."
    if ! mysql --user="${db_user}" --host="${db_host}" -e "DROP DATABASE IF EXISTS ${temp_db}; CREATE DATABASE ${temp_db};"; then
        log_message "ERROR" "Failed to create temporary database: ${temp_db}"
        unset MYSQL_PWD
        return 1
    fi
    
    # Decompress the base backup if needed
    local uncompressed_base=""
    if [[ "${base_backup_file}" == *.gz ]]; then
        uncompressed_base="${DB_RESTORE_DIR}/$(basename "${base_backup_file}" .gz)"
        log_message "INFO" "Decompressing base backup file..."
        if ! gunzip -c "${base_backup_file}" > "${uncompressed_base}"; then
            log_message "ERROR" "Failed to decompress base backup file: ${base_backup_file}"
            unset MYSQL_PWD
            return 1
        fi
    else
        uncompressed_base="${base_backup_file}"
    fi
    
    # Restore the base backup to the temporary database
    log_message "INFO" "Restoring base backup to temporary database..."
    if ! mysql --user="${db_user}" --host="${db_host}" "${temp_db}" < "${uncompressed_base}"; then
        log_message "ERROR" "Failed to restore base backup to temporary database"
        unset MYSQL_PWD
        return 1
    fi
    
    # Get the binary log position from the base backup
    local binlog_file=$(grep "CHANGE MASTER TO" "${uncompressed_base}" | grep -oP "MASTER_LOG_FILE='\\K[^']+" || echo "")
    local binlog_pos=$(grep "CHANGE MASTER TO" "${uncompressed_base}" | grep -oP "MASTER_LOG_POS=\\K[0-9]+" || echo "")
    
    if [[ -z "${binlog_file}" || -z "${binlog_pos}" ]]; then
        log_message "ERROR" "Could not extract binary log position from base backup"
        unset MYSQL_PWD
        return 1
    fi
    
    log_message "INFO" "Found binary log position: ${binlog_file}:${binlog_pos}"
    
    # Find binary log files that need to be applied
    local binary_logs=()
    while IFS= read -r log_file; do
        if [[ -n "${log_file}" ]]; then
            # Lexicographically compare the log file name
            if [[ "${log_file}" > "${binlog_file}" || "${log_file}" == "${binlog_file}" ]]; then
                binary_logs+=("${log_file}")
            fi
        fi
    done < <(find "${binary_logs_dir}" -name "*.bin" | sort)
    
    log_message "INFO" "Found ${#binary_logs[@]} binary log files to apply"
    
    # Apply binary logs up to the target timestamp
    for log_file in "${binary_logs[@]}"; do
        log_message "INFO" "Applying binary log: ${log_file}..."
        
        local start_position=1
        if [[ "$(basename "${log_file}")" == "${binlog_file}" ]]; then
            start_position="${binlog_pos}"
        fi
        
        if ! mysqlbinlog --start-position="${start_position}" --stop-datetime="${target_timestamp}" "${log_file}" | \
             mysql --user="${db_user}" --host="${db_host}" "${temp_db}"; then
            log_message "ERROR" "Failed to apply binary log: ${log_file}"
            unset MYSQL_PWD
            return 1
        fi
    done
    
    # Replace the original database with the recovered one
    log_message "INFO" "Replacing original database with recovered database..."
    if ! mysql --user="${db_user}" --host="${db_host}" -e "DROP DATABASE IF EXISTS ${db_name}; CREATE DATABASE ${db_name};"; then
        log_message "ERROR" "Failed to reset original database: ${db_name}"
        unset MYSQL_PWD
        return 1
    fi
    
    # Dump and restore the temporary database to the original
    log_message "INFO" "Transferring data from recovered database to original..."
    if ! mysqldump --user="${db_user}" --host="${db_host}" "${temp_db}" | mysql --user="${db_user}" --host="${db_host}" "${db_name}"; then
        log_message "ERROR" "Failed to transfer data from recovered database to original"
        unset MYSQL_PWD
        return 1
    fi
    
    # Drop the temporary database
    log_message "INFO" "Dropping temporary database..."
    if ! mysql --user="${db_user}" --host="${db_host}" -e "DROP DATABASE IF EXISTS ${temp_db};"; then
        log_message "WARNING" "Failed to drop temporary database: ${temp_db}"
    fi
    
    # Clean up temporary files
    if [[ "${base_backup_file}" == *.gz && -n "${uncompressed_base}" ]]; then
        rm -f "${uncompressed_base}"
    fi
    
    # Unset the MYSQL_PWD environment variable
    unset MYSQL_PWD
    
    log_message "SUCCESS" "Point-in-time recovery completed successfully for database ${db_name} to ${target_timestamp}"
    return 0
}

# Sends a notification about restoration status
send_notification() {
    local status="$1"
    local details="$2"
    
    log_message "INFO" "Sending restoration notification (status: ${status})..."
    
    local subject="[InsurePilot] Documents View Restoration ${status^^}"
    local message="Restoration Status: ${status^^}\n\nDetails:\n${details}\n\nTimestamp: $(date)"
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

# Cleans up temporary restoration files
cleanup_restore_files() {
    log_message "INFO" "Cleaning up temporary restoration files..."
    
    # Remove temporary restore directories
    if [[ -d "${RESTORE_DIR}" ]]; then
        rm -rf "${RESTORE_DIR}"
    fi
    
    log_message "INFO" "Temporary files cleaned up"
    return 0
}

# Displays script usage information
show_usage() {
    echo "Usage: $(basename "$0") [OPTIONS]"
    echo "Restoration script for the Documents View feature within InsurePilot"
    echo
    echo "Options:"
    echo "  -t, --type TYPE         Restoration type (database, files, config, all)"
    echo "  -s, --source SOURCE     Backup source (local, s3)"
    echo "  -f, --file FILE         Specific backup file to restore (optional)"
    echo "  -d, --database NAME     Database name to restore"
    echo "  -u, --user USER         Database user"
    echo "  -p, --password PASS     Database password"
    echo "  -h, --host HOST         Database host (default: localhost)"
    echo "  --target-dir DIR        Target directory for file or config restoration"
    echo "  --pitr TIMESTAMP        Perform point-in-time recovery to specified timestamp"
    echo "                          Format: 'YYYY-MM-DD HH:MM:SS'"
    echo "  --binary-logs DIR       Directory containing binary logs for point-in-time recovery"
    echo "  -k, --key-id ID         GPG key ID for decrypting encrypted backups"
    echo "  -v, --verify            Verify the restoration after completion"
    echo "  -n, --no-cleanup        Skip cleanup of temporary files"
    echo "  --test                  Test restoration process without applying changes"
    echo "  --help                  Display this help message"
    echo
    echo "Examples:"
    echo "  $(basename "$0") --type database --source local --database documents-view --user dbuser --password dbpass"
    echo "  $(basename "$0") --type files --source s3 --file documents_full_20230524_120000.tar.gz --target-dir /var/www/documents"
    echo "  $(basename "$0") --type all --source local --database documents-view --user dbuser --password dbpass --target-dir /var/www/html/documents-view"
}

# Main function that orchestrates the restoration process
main() {
    local restore_type=""
    local backup_source="local"
    local backup_file=""
    local db_name=""
    local db_user=""
    local db_password=""
    local db_host="localhost"
    local target_dir=""
    local pitr_timestamp=""
    local binary_logs_dir=""
    local gpg_key_id=""
    local verify=0
    local no_cleanup=0
    local test_mode=0
    local restore_db=0
    local restore_files=0
    local restore_config=0
    local exit_code=0
    local restore_status="success"
    local restore_details=""
    
    # Parse command line arguments
    while [[ $# -gt 0 ]]; do
        case "$1" in
            -t|--type)
                restore_type="$2"
                shift 2
                ;;
            -s|--source)
                backup_source="$2"
                shift 2
                ;;
            -f|--file)
                backup_file="$2"
                shift 2
                ;;
            -d|--database)
                db_name="$2"
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
            --target-dir)
                target_dir="$2"
                shift 2
                ;;
            --pitr)
                pitr_timestamp="$2"
                shift 2
                ;;
            --binary-logs)
                binary_logs_dir="$2"
                shift 2
                ;;
            -k|--key-id)
                gpg_key_id="$2"
                shift 2
                ;;
            -v|--verify)
                verify=1
                shift
                ;;
            -n|--no-cleanup)
                no_cleanup=1
                shift
                ;;
            --test)
                test_mode=1
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
    
    log_message "INFO" "Starting restoration process (type: ${restore_type}, source: ${backup_source})..."
    
    # Check if running in test mode
    if [[ ${test_mode} -eq 1 ]]; then
        log_message "INFO" "Running in TEST MODE - no changes will be applied"
    fi
    
    # Determine which components to restore based on restore_type
    case "${restore_type}" in
        "database")
            restore_db=1
            ;;
        "files")
            restore_files=1
            ;;
        "config")
            restore_config=1
            ;;
        "all")
            restore_db=1
            restore_files=1
            restore_config=1
            ;;
        *)
            log_message "ERROR" "Invalid restoration type: ${restore_type}"
            show_usage
            exit 1
            ;;
    esac
    
    # Check dependencies
    if ! check_dependencies; then
        log_message "ERROR" "Missing required dependencies, aborting restoration"
        restore_status="failure"
        restore_details="Missing required dependencies"
        send_notification "${restore_status}" "${restore_details}"
        return 1
    fi
    
    # Create restoration directories
    if ! create_restore_directories; then
        log_message "ERROR" "Failed to create restoration directories, aborting restoration"
        restore_status="failure"
        restore_details="Failed to create restoration directories"
        send_notification "${restore_status}" "${restore_details}"
        return 1
    fi
    
    # Array to store backup files
    local backup_files=()
    local local_backup_files=()
    
    # If specific backup file is provided, use it
    if [[ -n "${backup_file}" ]]; then
        if [[ "${backup_source}" == "s3" ]]; then
            # S3 path is different based on restore type
            local s3_path=""
            if [[ ${restore_db} -eq 1 ]]; then
                s3_path="database/${backup_file}"
            elif [[ ${restore_files} -eq 1 ]]; then
                s3_path="documents/${backup_file}"
            elif [[ ${restore_config} -eq 1 ]]; then
                s3_path="config/${backup_file}"
            fi
            
            local local_file="${RESTORE_DIR}/${backup_file}"
            
            if ! download_from_s3 "${s3_path}" "${local_file}"; then
                log_message "ERROR" "Failed to download backup file from S3: ${s3_path}"
                exit_code=1
                restore_status="failure"
                restore_details="Failed to download backup file: ${backup_file}"
            else
                local_backup_files+=("${local_file}")
                backup_files+=("${local_file}")
                restore_details="Downloaded backup file: ${backup_file}\n"
            fi
        else
            # For local source, check if file exists
            if [[ ! -f "${backup_file}" ]]; then
                log_message "ERROR" "Local backup file does not exist: ${backup_file}"
                exit_code=1
                restore_status="failure"
                restore_details="Local backup file does not exist: ${backup_file}"
            else
                backup_files+=("${backup_file}")
                restore_details="Using local backup file: ${backup_file}\n"
            fi
        fi
    else
        # If no specific file is provided, find the latest backup
        if [[ ${restore_db} -eq 1 ]]; then
            local db_backup=""
            if [[ "${backup_source}" == "s3" ]]; then
                local s3_db_backups=($(list_available_backups "database" "s3" "*.sql.gz" | head -n1))
                if [[ ${#s3_db_backups[@]} -gt 0 ]]; then
                    local s3_db_path="database/${s3_db_backups[0]}"
                    local local_db_file="${DB_RESTORE_DIR}/$(basename "${s3_db_backups[0]}")"
                    
                    if ! download_from_s3 "${s3_db_path}" "${local_db_file}"; then
                        log_message "ERROR" "Failed to download database backup from S3: ${s3_db_path}"
                        exit_code=1
                        restore_status="failure"
                        restore_details="${restore_details}Failed to download database backup\n"
                    else
                        local_backup_files+=("${local_db_file}")
                        db_backup="${local_db_file}"
                        restore_details="${restore_details}Downloaded database backup: $(basename "${s3_db_backups[0]}")\n"
                    fi
                else
                    log_message "ERROR" "No database backups found in S3"
                    exit_code=1
                    restore_status="failure"
                    restore_details="${restore_details}No database backups found in S3\n"
                fi
            else
                local local_db_backups=($(list_available_backups "database" "local" "*.sql.gz" | head -n1))
                if [[ ${#local_db_backups[@]} -gt 0 ]]; then
                    db_backup="${local_db_backups[0]}"
                    restore_details="${restore_details}Using local database backup: $(basename "${db_backup}")\n"
                else
                    log_message "ERROR" "No local database backups found"
                    exit_code=1
                    restore_status="failure"
                    restore_details="${restore_details}No local database backups found\n"
                fi
            fi
            
            if [[ -n "${db_backup}" ]]; then
                backup_files+=("${db_backup}")
            fi
        fi
        
        if [[ ${restore_files} -eq 1 ]]; then
            local files_backup=""
            if [[ "${backup_source}" == "s3" ]]; then
                local s3_files_backups=($(list_available_backups "files" "s3" "*.tar.gz" | head -n1))
                if [[ ${#s3_files_backups[@]} -gt 0 ]]; then
                    local s3_files_path="documents/${s3_files_backups[0]}"
                    local local_files_file="${FILE_RESTORE_DIR}/$(basename "${s3_files_backups[0]}")"
                    
                    if ! download_from_s3 "${s3_files_path}" "${local_files_file}"; then
                        log_message "ERROR" "Failed to download document files backup from S3: ${s3_files_path}"
                        exit_code=1
                        restore_status="failure"
                        restore_details="${restore_details}Failed to download document files backup\n"
                    else
                        local_backup_files+=("${local_files_file}")
                        files_backup="${local_files_file}"
                        restore_details="${restore_details}Downloaded document files backup: $(basename "${s3_files_backups[0]}")\n"
                    fi
                else
                    log_message "ERROR" "No document files backups found in S3"
                    exit_code=1
                    restore_status="failure"
                    restore_details="${restore_details}No document files backups found in S3\n"
                fi
            else
                local local_files_backups=($(list_available_backups "files" "local" "*.tar.gz" | head -n1))
                if [[ ${#local_files_backups[@]} -gt 0 ]]; then
                    files_backup="${local_files_backups[0]}"
                    restore_details="${restore_details}Using local document files backup: $(basename "${files_backup}")\n"
                else
                    log_message "ERROR" "No local document files backups found"
                    exit_code=1
                    restore_status="failure"
                    restore_details="${restore_details}No local document files backups found\n"
                fi
            fi
            
            if [[ -n "${files_backup}" ]]; then
                backup_files+=("${files_backup}")
            fi
        fi
        
        if [[ ${restore_config} -eq 1 ]]; then
            local config_backup=""
            if [[ "${backup_source}" == "s3" ]]; then
                local s3_config_backups=($(list_available_backups "config" "s3" "*.tar.gz" | head -n1))
                if [[ ${#s3_config_backups[@]} -gt 0 ]]; then
                    local s3_config_path="config/${s3_config_backups[0]}"
                    local local_config_file="${CONFIG_RESTORE_DIR}/$(basename "${s3_config_backups[0]}")"
                    
                    if ! download_from_s3 "${s3_config_path}" "${local_config_file}"; then
                        log_message "ERROR" "Failed to download configuration backup from S3: ${s3_config_path}"
                        exit_code=1
                        restore_status="failure"
                        restore_details="${restore_details}Failed to download configuration backup\n"
                    else
                        local_backup_files+=("${local_config_file}")
                        config_backup="${local_config_file}"
                        restore_details="${restore_details}Downloaded configuration backup: $(basename "${s3_config_backups[0]}")\n"
                    fi
                else
                    log_message "ERROR" "No configuration backups found in S3"
                    exit_code=1
                    restore_status="failure"
                    restore_details="${restore_details}No configuration backups found in S3\n"
                fi
            else
                local local_config_backups=($(list_available_backups "config" "local" "*.tar.gz" | head -n1))
                if [[ ${#local_config_backups[@]} -gt 0 ]]; then
                    config_backup="${local_config_backups[0]}"
                    restore_details="${restore_details}Using local configuration backup: $(basename "${config_backup}")\n"
                else
                    log_message "ERROR" "No local configuration backups found"
                    exit_code=1
                    restore_status="failure"
                    restore_details="${restore_details}No local configuration backups found\n"
                fi
            fi
            
            if [[ -n "${config_backup}" ]]; then
                backup_files+=("${config_backup}")
            fi
        fi
    fi
    
    # If no backups were found, exit
    if [[ ${#backup_files[@]} -eq 0 ]]; then
        log_message "ERROR" "No backup files to restore"
        restore_status="failure"
        restore_details="${restore_details}No backup files to restore\n"
        send_notification "${restore_status}" "${restore_details}"
        return 1
    fi
    
    # Decrypt backups if needed
    if [[ -n "${gpg_key_id}" ]]; then
        local decrypted_files=()
        for backup in "${backup_files[@]}"; do
            if [[ "${backup}" == *.gpg ]]; then
                log_message "INFO" "Decrypting backup file: ${backup}"
                local decrypted_file=""
                if ! decrypted_file=$(decrypt_backup "${backup}" "${gpg_key_id}"); then
                    log_message "ERROR" "Failed to decrypt backup file: ${backup}"
                    exit_code=1
                    restore_status="failure"
                    restore_details="${restore_details}Failed to decrypt backup: $(basename "${backup}")\n"
                else
                    decrypted_files+=("${decrypted_file}")
                    restore_details="${restore_details}Decrypted backup: $(basename "${backup}")\n"
                fi
            else
                # Not encrypted, keep as is
                decrypted_files+=("${backup}")
            fi
        done
        
        # Replace original array with decrypted files
        backup_files=("${decrypted_files[@]}")
    fi
    
    # Verify backup integrity using verify_backup from backup.sh
    for backup in "${backup_files[@]}"; do
        local backup_type=""
        if [[ "${backup}" == *"database"* || "${backup}" == *".sql"* ]]; then
            backup_type="database"
        elif [[ "${backup}" == *"documents"* || "${backup}" == *"files"* ]]; then
            backup_type="files"
        elif [[ "${backup}" == *"config"* ]]; then
            backup_type="config"
        else
            log_message "WARNING" "Could not determine backup type for: ${backup}"
            backup_type="unknown"
        fi
        
        if [[ "${backup_type}" != "unknown" ]]; then
            if ! verify_backup "${backup}" "${backup_type}"; then
                log_message "ERROR" "Backup integrity verification failed: ${backup}"
                exit_code=1
                restore_status="failure"
                restore_details="${restore_details}Backup integrity verification failed: $(basename "${backup}")\n"
                continue
            else
                restore_details="${restore_details}Backup integrity verified: $(basename "${backup}")\n"
            fi
        fi
    done
    
    # If in test mode, exit here after verification
    if [[ ${test_mode} -eq 1 ]]; then
        log_message "INFO" "Test mode: verification completed, stopping before actual restoration"
        restore_status="success"
        restore_details="${restore_details}Test mode: verification completed\n"
        send_notification "${restore_status}" "${restore_details}"
        
        # Clean up downloaded files
        if [[ ${no_cleanup} -eq 0 ]]; then
            cleanup_restore_files
        fi
        
        return 0
    fi
    
    # Perform database restoration if requested
    if [[ ${restore_db} -eq 1 ]]; then
        if [[ -z "${db_name}" || -z "${db_user}" || -z "${db_password}" ]]; then
            log_message "ERROR" "Database name, user, and password are required for database restoration"
            exit_code=1
            restore_status="failure"
            restore_details="${restore_details}Missing database parameters\n"
        else
            # Find the database backup file
            local db_backup_file=""
            for backup in "${backup_files[@]}"; do
                if [[ "${backup}" == *"database"* || "${backup}" == *".sql"* ]]; then
                    db_backup_file="${backup}"
                    break
                fi
            done
            
            if [[ -z "${db_backup_file}" ]]; then
                log_message "ERROR" "No database backup file found"
                exit_code=1
                restore_status="failure"
                restore_details="${restore_details}No database backup file found\n"
            else
                # If point-in-time recovery is requested
                if [[ -n "${pitr_timestamp}" ]]; then
                    if [[ -z "${binary_logs_dir}" ]]; then
                        log_message "ERROR" "Binary logs directory is required for point-in-time recovery"
                        exit_code=1
                        restore_status="failure"
                        restore_details="${restore_details}Missing binary logs directory for PITR\n"
                    else
                        log_message "INFO" "Performing point-in-time recovery..."
                        if ! perform_point_in_time_recovery "${db_backup_file}" "${binary_logs_dir}" "${pitr_timestamp}" "${db_name}" "${db_user}" "${db_password}" "${db_host}"; then
                            log_message "ERROR" "Point-in-time recovery failed"
                            exit_code=1
                            restore_status="failure"
                            restore_details="${restore_details}Point-in-time recovery failed\n"
                        else
                            restore_details="${restore_details}Point-in-time recovery successful to ${pitr_timestamp}\n"
                        fi
                    fi
                else
                    # Normal database restoration
                    log_message "INFO" "Restoring database..."
                    if ! restore_database "${db_backup_file}" "${db_name}" "${db_user}" "${db_password}" "${db_host}"; then
                        log_message "ERROR" "Database restoration failed"
                        exit_code=1
                        restore_status="failure"
                        restore_details="${restore_details}Database restoration failed\n"
                    else
                        restore_details="${restore_details}Database restoration successful\n"
                        
                        # Verify the database restoration if requested
                        if [[ ${verify} -eq 1 ]]; then
                            if ! verify_restoration "database" "${db_name}" "${db_user}" "${db_password}" "${db_host}"; then
                                log_message "ERROR" "Database restoration verification failed"
                                exit_code=1
                                restore_status="warning"
                                restore_details="${restore_details}Database restoration verification failed\n"
                            else
                                restore_details="${restore_details}Database restoration verified\n"
                            fi
                        fi
                    fi
                fi
            fi
        fi
    fi
    
    # Perform document files restoration if requested
    if [[ ${restore_files} -eq 1 ]]; then
        if [[ -z "${target_dir}" ]]; then
            log_message "ERROR" "Target directory is required for document files restoration"
            exit_code=1
            restore_status="failure"
            restore_details="${restore_details}Missing target directory\n"
        else
            # Find the document files backup file
            local files_backup_file=""
            for backup in "${backup_files[@]}"; do
                if [[ "${backup}" == *"documents"* || "${backup}" == *"files"* ]]; then
                    files_backup_file="${backup}"
                    break
                fi
            done
            
            if [[ -z "${files_backup_file}" ]]; then
                log_message "ERROR" "No document files backup file found"
                exit_code=1
                restore_status="failure"
                restore_details="${restore_details}No document files backup file found\n"
            else
                log_message "INFO" "Restoring document files..."
                if ! restore_document_files "${files_backup_file}" "${target_dir}"; then
                    log_message "ERROR" "Document files restoration failed"
                    exit_code=1
                    restore_status="failure"
                    restore_details="${restore_details}Document files restoration failed\n"
                else
                    restore_details="${restore_details}Document files restoration successful\n"
                    
                    # Verify the document files restoration if requested
                    if [[ ${verify} -eq 1 ]]; then
                        if ! verify_restoration "files" "${target_dir}"; then
                            log_message "ERROR" "Document files restoration verification failed"
                            exit_code=1
                            restore_status="warning"
                            restore_details="${restore_details}Document files restoration verification failed\n"
                        else
                            restore_details="${restore_details}Document files restoration verified\n"
                        fi
                    fi
                fi
            fi
        fi
    fi
    
    # Perform configuration restoration if requested
    if [[ ${restore_config} -eq 1 ]]; then
        if [[ -z "${target_dir}" ]]; then
            log_message "ERROR" "Target directory is required for configuration restoration"
            exit_code=1
            restore_status="failure"
            restore_details="${restore_details}Missing target directory\n"
        else
            # Find the configuration backup file
            local config_backup_file=""
            for backup in "${backup_files[@]}"; do
                if [[ "${backup}" == *"config"* ]]; then
                    config_backup_file="${backup}"
                    break
                fi
            done
            
            if [[ -z "${config_backup_file}" ]]; then
                log_message "ERROR" "No configuration backup file found"
                exit_code=1
                restore_status="failure"
                restore_details="${restore_details}No configuration backup file found\n"
            else
                log_message "INFO" "Restoring configuration..."
                if ! restore_configuration "${config_backup_file}" "${target_dir}"; then
                    log_message "ERROR" "Configuration restoration failed"
                    exit_code=1
                    restore_status="failure"
                    restore_details="${restore_details}Configuration restoration failed\n"
                else
                    restore_details="${restore_details}Configuration restoration successful\n"
                    
                    # Verify the configuration restoration if requested
                    if [[ ${verify} -eq 1 ]]; then
                        if ! verify_restoration "config" "${target_dir}"; then
                            log_message "ERROR" "Configuration restoration verification failed"
                            exit_code=1
                            restore_status="warning"
                            restore_details="${restore_details}Configuration restoration verification failed\n"
                        else
                            restore_details="${restore_details}Configuration restoration verified\n"
                        fi
                    fi
                fi
            fi
        fi
    fi
    
    # Send notification about restoration status
    send_notification "${restore_status}" "${restore_details}"
    
    # Clean up temporary files
    if [[ ${no_cleanup} -eq 0 ]]; then
        cleanup_restore_files
    fi
    
    if [[ ${exit_code} -eq 0 ]]; then
        log_message "SUCCESS" "Restoration process completed successfully"
    else
        log_message "ERROR" "Restoration process completed with errors"
    fi
    
    return ${exit_code}
}

# Execute the main function with all arguments
main "$@"