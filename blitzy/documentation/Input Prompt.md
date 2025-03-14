```
# IP250 - Documents View: Feature Requirements

**TABLE OF CONTENTS**

# **#1: WHY - VISION & PURPOSE**

**Purpose:**
The “Documents View” provides a dedicated, full-screen environment for users within the Insure Pilot platform to review, process, and manage documents. It centralizes document-related actions—such as viewing the file, editing metadata, and navigating to related records—while maintaining a clear and intuitive workflow. This ensures that users can efficiently handle claims, policies, and agent-related documentation, improving accuracy, organization, and overall operational efficiency.

**Users (for context only):**

- **Claims Adjusters, Underwriters, and Support Staff:** Those who need to review and manage documents, apply metadata, link documents to policies or agents, and maintain accurate records.
- **Supervisors and Administrators**: Individuals who oversee document handling, ensure compliance, and review audit trails, document history, and changes over time.

# **#2: WHAT - CORE REQUIREMENTS**

**Functional Requirements**

1. **Document Viewing & Interface Layout**
    - **Full-Screen Lightbox View:**
        - The document viewer must open in a full-screen overlay (“lightbox”) above the main interface, minimizing distractions and providing a focused environment for document review.
    - **Left Pane (Document Display):**
        - Integrate with Adobe Acrobat PDF viewer for document display.
        - User must be able to scroll through and view multipage PDFs.
        - The document’s filename and PDF navigation controls must be readily accessible.
    - **Right Pane (Actions & Metadata):**
        - Display editable metadata fields, actions (e.g., mark as processed), and utility links (e.g., “Document History”).
        - Include indicators for “Saving…” or “Saved” states when the user modifies metadata.
2. **Document Metadata & Fields**
    - The following fields must be displayed and editable (unless noted otherwise). Each field pulls from or writes to the underlying data sources as mapped below:
        - Policy Number (Dropdown): Sourced from `map_producer_policy` -> `get policy_id` -> `policy.policy_prefix_id` -> `policy_prefix.name CONCAT WITH policy.number`.
            - Provides a list of available policy numbers.
            - Filtering must occur as the user types, narrowing down available policy IDs.
        - Loss Sequence (Dropdown): Sourced from `map_policy_loss` by `policy_id` -> `get loss.id` -> `Order by loss.date ascending` -> sequence numerically starting with 1.
            - Dependent on a selected Policy Number.
            - Displays available loss sequences tied to the chosen policy.
            - Filters as the user types to quickly locate the appropriate loss sequence.
        - Claimant (Dropdown): Sourced from `map_policy_loss` by `policy_id` -> `get loss.id` -> `map_loss_claimant` by `loss_id` -> `get claimant.id` -> Order by `claimant.created_at ascending` -> sequence numerically starting with 1.
            - Dependent on the chosen Loss Sequence.
            - Displays claimants associated with the selected policy and loss.
            - Filters dynamically as the user types.
        - Document Description (Dropdown): Sourced from `document.description` for display to save.
            - Provides categorized document types.
            - User can filter by typing.
        - Assigned To (Dropdown): Links to `user.username` and `user_group.name` for display options -> `map_user_document` and `map_user_group_document` to save.
            - Assigns the document to a user or user group.
            - Filters options as the user types.
            - Filters options as the user types.
        - Producer Number (Dropdown): Sourced from `map_producer_policy` by `policy_id` -> `get producer_id` -> `get producer`-> `producer.number`.
            - If the document is initially uploaded to a producer, this field is auto-populated.
            - If present, selecting a producer number filters the available Policy Number options based on the Policy Number’s that the agent is related to.
3. **Full Mapping of Front-End Fields to Database Fields**
    - **Policy Number** → `map_producer_policy` -> `get policy_id` -> `policy.policy_prefix_id` -> `policy_prefix.name CONCAT WITH policy.number`
    - **Loss Sequence** → `map_policy_loss` by `policy_id` -> `get loss.id` -> Order by `loss.date ascending` -> sequence numerically starting with 1 (first one for policy)
    - **Claimant** → `map_policy_loss` by `policy_id` -> `get loss.id` -> `map_loss_claimant` by `loss_id` -> `get claimant.id` -> Order by `claimant.created_at ascending` -> sequence numerically starting with 1 (first one for the policy and loss)
    - **Document Type** → `document_type.name` for display -> `document.document_type_id` to save with `document_type.id`
    - **Document Description** → `document.description` for display to save
    - **Assigned To** → `user.username` and `user_group.name` for display options -> `map_user_document` and `map_user_group_document` to save
    - **Producer Number** → `map_producer_policy` by `policy_id` -> `get producer_id` -> `get producer` -> `producer.number`
    - **Mark as Processed** → Add a row to `map_document_action` with `action_type.id` for processed
    - **Add a Note** → Add a row to `note` and a row to `map_document_note` and any other map tables it should be associated with
    - **Policy Driver Name** → `map_policy_driver` for `driver_id`, `map_driver_name` for `name_id`, then `name.first` etc.
    - **Claimant info** → `map_policy_loss` by `policy_id` -> get [`loss.id`](http://loss.id/) -> `map_loss_claimant` by `loss_id` -> get [`claimant.id`](http://claimant.id/) -> get claimant information
    - **Trash Document** → Add a row to `map_document_action` with `action_type.id` for trashed
    - **Loss Information** → `map_policy_loss` by `policy_id` -> get [`loss.id`](http://loss.id/) -> get loss information
    - **Document History** → `map_document_action` by `document_id`
    - **Document Notes** → `map_document_note` by `document_id`
    - **Document Assignment** → save to `map_user_document` and/or `map_user_group_document`, then update `document_action` table
    - **Document Changes** → Save record to `action`, `map_document_action` with appropriate `action_id`, and document
    - **Document Create Audit** → `document.created_at`, `document.created_by`
    - **Document Last Audit** → `query map_document_action` by `document.id`; the last record shows last `updated_by` and `updated_at`
    - **File Name** → `map_document_file` by [`document.id](http://document.id/)` -> get `file_id` -> [file.name](http://file.name/)
    - **Saved** → `map_document_action` table by `document.id` for saved
4. **Document Actions**
    - **Mark as Processed:**
        - When selected, the system must create a record in `map_document_action` with `action_type.id` corresponding to “processed.”
        - Once processed, all metadata fields become read-only, and the button changes its label to “Processed.”
        - Selecting the button again will revert the fields to editable mode and remove the “processed” action state (another action record created to reflect unprocessed state).
    - **Trash Document:**
        - Clicking the trash icon moves the document to the “Recently Deleted” folder.
        - A record in `map_document_action` is created with `action_type.id` for “trashed.”
        - Document stays recoverable for 90 days.
5. **Secondary Actions**
    - **Ellipsis Menu:** Presents dynamic navigation options if applicable:
        - **Go to Producer View:** If `producer_id` is present, linking the user to the corresponding agent’s detail page.
        - **Go to Policy:** If `policy_id` is present, linking the user to the related policy’s home screen.
        - **Go to Claimant View:** If `claimant_id` is present, linking the user to that claimant’s detail screen.
6. **Document History & Audit Trail**
    - **Document History Link:**
        - Clicking “Document History” displays a history overlay above the right panel.
        - The top section shows “Last Edited” (timestamp, user), “Last Edited By” (user), and similar audit metadata pulled from `map_document_action` with [`document.id](http://document.id/).`
        - Below, a chronological list of changes and actions (e.g., processed, trashed, change made) is shown, starting from the most recent.
        - The “Back” button returns the user to the main metadata panel.
    - **Document Changes & Auditing:**
        - Each save action (field edit or processed state toggle) must log a corresponding record in `map_document_action`.
        - The system must track `document.created_at`, `document.created_by`, and the last updated user and timestamp (from the latest record in `map_document_action`).
    - **Saved Indicator:**
        - On any metadata change, “Saving…” appears.
        - Once changes are committed, “Saved” is displayed.
        - The system determines save completion by referencing `map_document_action`
    1. **Interaction Behavior**
        - **Dropdown Controls:**
            - All dropdown fields must support type-ahead filtering.
            - If a field is dependent on another (e.g., Claimant depends on Loss Sequence), changing the first field must immediately filter or refresh the dependent field’s options.
        - **Read-Only vs Editable States:**
            - If the document is marked as processed, all fields switch to read-only mode.
            - Unprocessing the document reverts fields to editable mode.
        1. **Integration Requirements**
            - Adobe Acrobat PDF Viewer Integration:
            - The system must integrate with Adobe’s PDF viewer to display documents in the left pane.
            - Must support standard PDF navigation (scrolling pages, zooming, etc.).
            - Document’s filename should be accessible via `map_document_file` and displayed as a title or heading near the PDF viewer.
    
    # **#3: HOW - Planning & Implementation**
    
    **Integration Considerations:**
    
    - **Adobe Acrobat PDF Viewer**
        - Ensure secure document rendering.
    - **Dropdown Data Sources**
        - Data for fields (policy numbers, claimants, agents, etc.) must be retrieved and filtered efficiently. Ensure dependencies are respected (e.g., claimant list refreshes after a loss sequence is chosen).
    - **Audit Logging:**
        - Actions on the document must consistently write to `map_document_action` tables. Implement a reliable mechanism to ensure all changes are tracked and associated with the correct `document.id`.
    - **Trash and Recovery Logic:**
        - Implement a process to move documents to “Recently Deleted” without losing their associated data. After 90 days, documents may be permanently removed or archived.

# **#4: USER EXPERIENCE (UX) & FLOWS**

**Flow: Viewing and Editing a Document**

1. **Initiate Document View:**
    1. User selects a document from a previous screen. A full-screen overlay opens showing the PDF on the left and metadata on the right.
2. **Editing Metadata:**
    1. User modifies policy number, claimant, or other fields. “Saving…” appears, then “Saved” once the system commits changes.
3. **Marking as Processed:**
    1. User clicks “Mark as Processed.” Fields become read-only, and “Processed” replaces the button text. Clicking again toggles back to editable mode.
4. **Viewing Document History:**
    1. User clicks “Document History.” A panel slides over displaying an audit trail. They may review who made the last edits. Clicking “Back” returns to the main panel.
5. **Trashing the Document:**
    1. If the user clicks the trash icon, a confirmation prompt appears. Confirming moves the document to “Recently Deleted” and logs an action in `map_document_action`.

**Flow: Navigating to Related Records**

1. Ellipsis Menu:
    1. If applicable, the user selects “Go to Agent View,” “Go to Policy,” or “Go to Claimant View” from the ellipsis menu.
    2. The system navigates them to the corresponding screen, providing contextual continuity.

# #5: BUSINESS REQUIREMENTS

**Access & Authentication:**

- Only authenticated users with appropriate roles can view and edit the Documents View.
- Access to certain fields or actions may be role-based (e.g., only supervisors can trash documents or mark them as processed).

**Data Validation & Integrity:**

- Dropdown fields must only display valid, relevant data filtered by upstream selections (e.g., Agent Number filters available Policies).
- Any changes to metadata must be immediately logged.

**Compliance & Auditing:**

- All changes (edits, processed state toggles, trash actions) must be recorded for auditing and compliance.
- The Document History overlay must provide a clear and easily reviewable audit trail.

**Operational Requirements:**

- Metadata editing and state changes must not disrupt the viewing experience (e.g., the user can continue reading the PDF while changes save in the background).
- Recoverability from Recently Deleted must be preserved for 90 days, aligning with retention policies.

# #6: MASTER TABLE SCHEMAS

`map_producer_policy`

| id | BIGINT | AUTO_INCREMENT, PRIMARY KEY | Unique identifier for the mapping record. |
| --- | --- | --- | --- |
| producer_id | BIGINT | NOT NULL, FOREIGN KEY to producer(id) | References the producer associated with the policy. |
| policy_id | BIGINT | NOT NULL, FOREIGN KEY to policy(id) | References the policy related to the producer. |
| description | TEXT |  | Additional details about the producer-policy relationship. |
| status_id | BIGINT | FOREIGN KEY to status(id) | References the status of the mapping record. |
| created_by | BIGINT | NOT NULL, FOREIGN KEY to user(id) | User who created the mapping record. |
| updated_by | BIGINT | FOREIGN KEY to user(id) | User who last updated the mapping record. |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Timestamp when the record was created. |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Timestamp when the record was last updated. |

`policy`

| **Column Name** | **Data Type** | **Constraints** | **Description** |
| --- | --- | --- | --- |
| id | BIGINT | AUTO_INCREMENT, PRIMARY KEY | Unique identifier for the policy record. |
| policy_prefix_id | BIGINT | NULLABLE, FOREIGN KEY to policy_prefix(id) | References the policy prefix. |
| number | VARCHAR(50) | NULLABLE, UNIQUE(policy_prefix_id, number) | Unique policy identifier within a prefix scope. |
| policy_type_id | BIGINT | NOT NULL, FOREIGN KEY to policy_type(id) | References the type of policy. |
| effective_date | DATE | NULLABLE | The start date of the policy. |
| inception_date | DATE | NULLABLE | The inception date of the policy (first effective date). |
| expiration_date | DATE | NOT NULL | The end date of the policy. |
| renewal_date | DATE | NULLABLE | Date when the policy is set for renewal. |
| status_id | BIGINT | NOT NULL, FOREIGN KEY to status(id) | References the status of the policy. |
| term_id | BIGINT | NOT NULL, FOREIGN KEY to term(id) | References the term of the policy. |
| description | TEXT | NULLABLE | Additional details about the policy. |
| created_by | BIGINT | NOT NULL, FOREIGN KEY to user(id) | User who created the record. |
| updated_by | BIGINT | FOREIGN KEY to user(id) | User who last updated the record. |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Timestamp when the record was created. |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Timestamp when the record was last updated. |

`policy_prefix`

| **Column Name** | **Data Type** | **Constraints** | **Description** |
| --- | --- | --- | --- |
| id | BIGINT | AUTO_INCREMENT, PRIMARY KEY | Unique identifier for each policy prefix. |
| name | VARCHAR(50) | NOT NULL, UNIQUE | Unique policy prefix (e.g., 'PLCY-', 'AUTO-'). |
| description | TEXT |  | Additional details about the policy prefix. |
| status_id | BIGINT | NOT NULL, FOREIGN KEY to status(id) | References the status of the policy prefix. |
| created_by | BIGINT | NOT NULL, FOREIGN KEY to user(id) | User who created the record. |
| updated_by | BIGINT | FOREIGN KEY to user(id) | User who last updated the record. |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Timestamp when the record was created. |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Timestamp when the record was last updated. |

`producer`

| **Column Name** | **Data Type** | **Constraints** | **Description** |
| --- | --- | --- | --- |
| id | BIGINT | AUTO_INCREMENT, PRIMARY KEY | Unique identifier for each producer/agency. |
| producer_code_id | BIGINT | NOT NULL, FOREIGN KEY to producer_code(id), UNIQUE | Unique producer code for identification. |
| number | VARCHAR(50) | NOT NULL, UNIQUE | Unique number assigned to the producer. |
| name | VARCHAR(255) | NOT NULL, UNIQUE | Name of the producer/agency. |
| description | TEXT |  | Additional details about the producer. |
| status_id | BIGINT | FOREIGN KEY to status(id) | References the status of the producer. |
| producer_type_id | BIGINT | NOT NULL, FOREIGN KEY to producer_type(id) | References the type of producer associated with the program. |
| signature_required | BOOLEAN | NOT NULL, DEFAULT FALSE | Specifies if a signature is required for the producer. |
| created_by | BIGINT | NOT NULL, FOREIGN KEY to user(id) | User who created the producer record. |
| updated_by | BIGINT | FOREIGN KEY to user(id) | User who last updated the producer record. |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Timestamp when the record was created. |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Timestamp when the record was last updated. |

`map_policy_loss`

| **Column Name** | **Data Type** | **Constraints** | **Description** |
| --- | --- | --- | --- |
| id | BIGINT | AUTO_INCREMENT, PRIMARY KEY | Unique identifier for the policy-loss mapping record. |
| policy_id | BIGINT | NOT NULL, FOREIGN KEY to policy(id) | References the associated policy. |
| loss_id | BIGINT | NOT NULL, FOREIGN KEY to loss(id) | References the associated loss. |
| description | TEXT |  | Additional details about the policy-loss mapping. |
| status_id | BIGINT | FOREIGN KEY to status(id) | Status of the policy-loss mapping (e.g., active, settled). |
| created_by | BIGINT | NOT NULL, FOREIGN KEY to user(id) | User who created the policy-loss record. |
| updated_by | BIGINT | FOREIGN KEY to user(id) | User who last updated the policy-loss record. |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Timestamp when the record was created. |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Timestamp when the record was last updated. |

`loss`

| **Column Name** | **Data Type** | **Constraints** | **Description** |
| --- | --- | --- | --- |
| id | BIGINT | AUTO_INCREMENT, PRIMARY KEY | Unique identifier for the loss record. |
| name | VARCHAR(50) | NOT NULL, UNIQUE | Name of the loss (e.g., 'Theft', 'Accident'). |
| date | DATE | NOT NULL | Date when the loss occurred. |
| description | TEXT |  | Additional details about the loss. |
| status_id | BIGINT | FOREIGN KEY to status(id) | References the status of the loss. |
| loss_type_id | BIGINT | NOT NULL, FOREIGN KEY to loss_type(id) | References the type of loss. |
| created_by | BIGINT | NOT NULL, FOREIGN KEY to user(id) | User who created the loss record. |
| updated_by | BIGINT | FOREIGN KEY to user(id) | User who last updated the loss record. |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Timestamp when the loss record was created. |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Timestamp when the loss record was last updated. |

`map_loss_claimant`

| **Column Name** | **Data Type** | **Constraints** | **Description** |
| --- | --- | --- | --- |
| id | BIGINT | AUTO_INCREMENT, PRIMARY KEY | Unique identifier for the loss-claimant mapping record. |
| loss_id | BIGINT | NOT NULL, FOREIGN KEY to loss(id) | References the associated loss. |
| claimant_id | BIGINT | NOT NULL, FOREIGN KEY to claimant(id) | References the associated claimant. |
| description | TEXT |  | Additional details about the loss-claimant mapping. |
| status_id | BIGINT | FOREIGN KEY to status(id) | Status of the loss-claimant mapping (e.g., pending, settled). |
| created_by | BIGINT | NOT NULL, FOREIGN KEY to user(id) | User who created the loss-claimant record. |
| updated_by | BIGINT | FOREIGN KEY to user(id) | User who last updated the loss-claimant record. |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Timestamp when the record was created. |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Timestamp when the record was last updated. |

`claimant`

| **Column Name** | **Data Type** | **Constraints** | **Description** |
| --- | --- | --- | --- |
| id | BIGINT | AUTO_INCREMENT, PRIMARY KEY | Unique identifier for the claimant record. |
| name_id | BIGINT | NOT NULL, FOREIGN KEY to name(id) | References the claimant's name record. |
| policy_id | BIGINT | FOREIGN KEY to policy(id) | References the associated policy record. |
| loss_id | BIGINT | FOREIGN KEY to loss(id) | References the associated loss record. |
| description | TEXT |  | Additional details about the claimant. |
| status_id | BIGINT | FOREIGN KEY to status(id) | References the status of the claimant. |
| claimant_type_id | BIGINT | NOT NULL, FOREIGN KEY to claimant_type(id) | References the type of claimant. |
| created_by | BIGINT | NOT NULL, FOREIGN KEY to user(id) | User who created the claimant record. |
| updated_by | BIGINT | FOREIGN KEY to user(id) | User who last updated the claimant record. |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Timestamp when the claimant record was created. |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Timestamp when the claimant record was last updated. |

`document`

| **Column Name** | **Data Type** | **Constraints** | **Description** |
| --- | --- | --- | --- |
| id | BIGINT | AUTO_INCREMENT, PRIMARY KEY | Unique identifier for the document. |
| name | TEXT | NOT NULL | Name of the document. |
| date_received | DATE | NULLABLE | Date when the document was received. |
| description | TEXT | NULLABLE | Additional details about the document. |
| signature_required | BOOLEAN | NOT NULL, DEFAULT FALSE | Specifies if a signature is required for the document. |
| status_id | BIGINT | FOREIGN KEY to status(id) | References the status of the document. |
| created_by | BIGINT | NOT NULL, FOREIGN KEY to user(id) | User who created the document record. |
| updated_by | BIGINT | FOREIGN KEY to user(id) | User who last updated the document record. |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Timestamp when the document record was created. |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Timestamp when the record was last updated. |

`document_type`

| **Column Name** | **Data Type** | **Constraints** | **Description** |
| --- | --- | --- | --- |
| id | BIGINT | AUTO_INCREMENT, PRIMARY KEY | Unique identifier for the document type. |
| name | VARCHAR(255) | NOT NULL, UNIQUE | Name of the document type (e.g., 'Policy Document'). |
| description | TEXT |  | Additional details about the document type. |
| status_id | BIGINT | FOREIGN KEY to status(id) | References the status of the document type. |
| created_by | BIGINT | NOT NULL, FOREIGN KEY to user(id) | References the user who created the record. |
| updated_by | BIGINT | FOREIGN KEY to user(id) | References the user who last updated the record. |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Timestamp when the record was created. |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Timestamp when the record was last updated. |

`user`

| **Column Name** | **Data Type** | **Constraints** | **Description** |
| --- | --- | --- | --- |
| id | BIGINT | AUTO_INCREMENT, PRIMARY KEY | Unique identifier for each user. |
| user_type_id | BIGINT | NOT NULL, FOREIGN KEY to user_type(id) | References the type of user (e.g., insured, producer, claims, system). |
| user_group_id | BIGINT | NOT NULL, FOREIGN KEY to user_group(id) | References the user group (e.g., manager, csr, underwriting). |
| username | VARCHAR(255) | UNIQUE, NOT NULL | Username |
| email | VARCHAR(255) | UNIQUE, NOT NULL | User's email (must be unique). |
| password | VARCHAR(255) | NOT NULL | User's hashed password. |
| description | TEXT |  | Additional details about the user. |
| status_id | BIGINT | FOREIGN KEY to status(id) | References the user's status (e.g., Active, Inactive). |
| created_by | BIGINT | FOREIGN KEY to user(id) | User who created the record. |
| updated_by | BIGINT | FOREIGN KEY to user(id) | User who last updated the record. |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Timestamp when the record was created. |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Timestamp when the record was last updated. |

`user_group`

| **Column Name** | **Data Type** | **Constraints** | **Description** |
| --- | --- | --- | --- |
| id | BIGINT | AUTO_INCREMENT, PRIMARY KEY | Unique identifier for each user group. |
| name | VARCHAR(255) | NOT NULL, UNIQUE | Name of the user group (e.g., CSR, manager, underwriter). |
| description | TEXT |  | Additional details about the user group. |
| status_id | BIGINT | FOREIGN KEY to status(id) | Status of the user group. |
| created_by | BIGINT | NOT NULL, FOREIGN KEY to user(id) | User who created the record. |
| updated_by | BIGINT | FOREIGN KEY to user(id) | User who last updated the record. |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Timestamp when the record was created. |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Timestamp when the record was last updated. |

`map_user_document`

| **Column Name** | **Data Type** | **Constraints** | **Description** |
| --- | --- | --- | --- |
| id | BIGINT | AUTO_INCREMENT, PRIMARY KEY | Unique identifier for the mapping |
| user_id | BIGINT | NOT NULL, FOREIGN KEY to user(id) | References the user table |
| document_id | BIGINT | NOT NULL, FOREIGN KEY to document(id) | References the document table |
| description | TEXT |  | Additional details about the mapping |
| status_id | BIGINT | NOT NULL, FOREIGN KEY to status(id) | References the status table |
| created_by | BIGINT | NOT NULL, FOREIGN KEY to user(id) | User who created the mapping |
| updated_by | BIGINT | FOREIGN KEY to user(id) | User who last updated the mapping |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Timestamp when the mapping was created |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Timestamp when the mapping was last updated |

`map_user_group_document`

| **Column Name** | **Data Type** | **Constraints** | **Description** |
| --- | --- | --- | --- |
| id | BIGINT | AUTO_INCREMENT, PRIMARY KEY | Unique identifier for the mapping |
| user_group_id | BIGINT | NOT NULL, FOREIGN KEY to user_group(id) | References the user group table |
| document_id | BIGINT | NOT NULL, FOREIGN KEY to document(id) | References the document table |
| description | TEXT |  | Additional details about the mapping |
| status_id | BIGINT | NOT NULL, FOREIGN KEY to status(id) | References the status table |
| created_by | BIGINT | NOT NULL, FOREIGN KEY to user(id) | User who created the mapping |
| updated_by | BIGINT | FOREIGN KEY to user(id) | User who last updated the mapping |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Timestamp when the mapping was created |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Timestamp when the mapping was last updated |

`map_document_action`

| **Column Name** | **Data Type** | **Constraints** | **Description** |
| --- | --- | --- | --- |
| id | BIGINT | AUTO_INCREMENT, PRIMARY KEY | Unique identifier for the mapping record. |
| document_id | BIGINT | NOT NULL, FOREIGN KEY to document(id) | References the document being acted upon. |
| action_id | BIGINT | NOT NULL, FOREIGN KEY to action(id) | References the action performed on the document. |
| description | TEXT |  | Additional details about the action performed. |
| status_id | BIGINT | NOT NULL, FOREIGN KEY to status(id) | Status of the mapping. |
| created_by | BIGINT | NOT NULL, FOREIGN KEY to user(id) | User who created the mapping record. |
| updated_by | BIGINT | FOREIGN KEY to user(id) | User who last updated the mapping record. |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Timestamp when the record was created. |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Timestamp when the record was last updated. |

`action_type`

| **Column Name** | **Data Type** | **Constraints** | **Description** |
| --- | --- | --- | --- |
| id | BIGINT | AUTO_INCREMENT, PRIMARY KEY | Unique identifier for the action type record. |
| name | VARCHAR(50) | NOT NULL, UNIQUE | Name of the action type (e.g., 'System', 'Manual'). |
| description | TEXT |  | Additional details about the action type. |
| status_id | BIGINT | FOREIGN KEY to status(id) | References the status of the action type. |
| created_by | BIGINT | NOT NULL, FOREIGN KEY to user(id) | User who created the action type record. |
| updated_by | BIGINT | FOREIGN KEY to user(id) | User who last updated the action type record. |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Timestamp when the record was created. |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Timestamp when the record was last updated. |

`action`

| **Column Name** | **Data Type** | **Constraints** | **Description** |
| --- | --- | --- | --- |
| id | BIGINT | AUTO_INCREMENT, PRIMARY KEY | Unique identifier for the action record. |
| record_id | BIGINT | NOT NULL | ID of the impacted record. |
| action_type_id | BIGINT | NOT NULL, FOREIGN KEY to action_type(id) | References the type of action performed. |
| description | TEXT |  | Additional details about the action. |
| status_id | BIGINT | FOREIGN KEY to status(id) | References the status of the action. |
| created_by | BIGINT | NOT NULL, FOREIGN KEY to user(id) | User who performed the action. |
| updated_by | BIGINT | FOREIGN KEY to user(id) | User who last updated the action record. |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Timestamp when the action record was created. |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Timestamp when the action record was last updated. |

`note`

| **Column Name** | **Data Type** | **Constraints** | **Description** |
| --- | --- | --- | --- |
| id | BIGINT | AUTO_INCREMENT, PRIMARY KEY | Unique identifier for each note record. |
| name | VARCHAR(50) | NOT NULL, UNIQUE | Name or title of the note. |
| description | TEXT |  | Content or additional details about the note. |
| status_id | BIGINT | FOREIGN KEY to status(id) | References the status of the note. |
| note_type_id | BIGINT | NOT NULL, FOREIGN KEY to note_type(id) | References the type of the note. |
| created_by | BIGINT | NOT NULL, FOREIGN KEY to user(id) | User who created the note record. |
| updated_by | BIGINT | FOREIGN KEY to user(id) | User who last updated the note record. |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Timestamp when the record was created. |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Timestamp when the record was last updated. |

`map_document_note`

| **Column Name** | **Data Type** | **Constraints** | **Description** |
| --- | --- | --- | --- |
| id | BIGINT | AUTO_INCREMENT, PRIMARY KEY | Unique identifier for the mapping record. |
| document_id | BIGINT | NOT NULL, FOREIGN KEY to document(id) | References the related document. |
| note_id | BIGINT | NOT NULL, FOREIGN KEY to note(id) | References the associated note. |
| description | TEXT |  | Additional details about the note. |
| status_id | BIGINT | NOT NULL, FOREIGN KEY to status(id) | Status of the mapping. |
| created_by | BIGINT | NOT NULL, FOREIGN KEY to user(id) | User who created the mapping record. |
| updated_by | BIGINT | FOREIGN KEY to user(id) | User who last updated the mapping record. |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Timestamp when the record was created. |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Timestamp when the record was last updated. |

`map_policy_driver`

| **Column Name** | **Data Type** | **Constraints** | **Description** |
| --- | --- | --- | --- |
| id | BIGINT | AUTO_INCREMENT, PRIMARY KEY | Unique identifier for each mapping. |
| policy_id | BIGINT | NOT NULL, FOREIGN KEY to policy(id) | References the associated policy. |
| driver_id | BIGINT | NOT NULL, FOREIGN KEY to driver(id) | References the associated driver. |
| description | TEXT |  | Additional details about the policy-driver relationship. |
| status_id | BIGINT | NOT NULL, FOREIGN KEY to status(id) | Status of the mapping (e.g., active, inactive). |
| created_by | BIGINT | NOT NULL, FOREIGN KEY to user(id) | User who created the mapping. |
| updated_by | BIGINT | FOREIGN KEY to user(id) | User who last updated the mapping. |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Timestamp when the record was created. |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Timestamp when the record was last updated. |
| Unique Constraint |  | UNIQUE (policy_id, driver_id) | Ensures no duplicate policy-driver mapping. |

`driver`

| **Column Name** | **Data Type** | **Constraints** | **Description** |
| --- | --- | --- | --- |
| id | BIGINT | AUTO_INCREMENT, PRIMARY KEY | Unique identifier for each driver record. |
| date_of_birth | DATE | NOT NULL | Driver's date of birth. |
| name_id | BIGINT | NOT NULL, FOREIGN KEY to name(id) | References the driver's name record. |
| license_id | BIGINT | NOT NULL, FOREIGN KEY to license(id) | References the driver's license record. |
| driver_type_id | BIGINT | NOT NULL, FOREIGN KEY to driver_type(id) | References the type of driver (e.g., Included). |
| status_id | BIGINT | NOT NULL, FOREIGN KEY to status(id) | References the status of the driver. |
| description | TEXT |  | Additional details about the driver. |
| signature_required | BOOLEAN | NOT NULL, DEFAULT FALSE | Specifies if a signature is required for the driver. |
| created_by | BIGINT | NOT NULL, FOREIGN KEY to user(id) | User who created the record. |
| updated_by | BIGINT | FOREIGN KEY to user(id) | User who last updated the record. |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Timestamp when the record was created. |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Timestamp when the record was last updated. |

`map_driver_name`

| **Column Name** | **Data Type** | **Constraints** | **Description** |
| --- | --- | --- | --- |
| id | BIGINT UNSIGNED | AUTO_INCREMENT, PRIMARY KEY | Unique identifier for the mapping record. |
| driver_id | BIGINT UNSIGNED | NOT NULL, FOREIGN KEY to driver(id) | References the driver record. |
| first_name_id | BIGINT UNSIGNED | NOT NULL, FOREIGN KEY to name(id) | References the first name of the driver. |
| middle_name_id | BIGINT UNSIGNED | FOREIGN KEY to name(id) | References the middle name of the driver. |
| last_name_id | BIGINT UNSIGNED | NOT NULL, FOREIGN KEY to name(id) | References the last name of the driver. |
| surname_name_id | BIGINT UNSIGNED | FOREIGN KEY to name(id) | References the surname of the driver. |
| description | TEXT |  | Additional details about the mapping. |
| status_id | BIGINT UNSIGNED | FOREIGN KEY to status(id) | References the status of the mapping. |
| created_by | BIGINT UNSIGNED | NOT NULL, FOREIGN KEY to user(id) | User who created the record. |
| updated_by | BIGINT UNSIGNED | FOREIGN KEY to user(id) | User who last updated the record. |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Timestamp when the record was created. |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Timestamp when the record was last updated. |

`name`

| **Column Name** | **Data Type** | **Constraints** | **Description** |
| --- | --- | --- | --- |
| id | BIGINT UNSIGNED | AUTO_INCREMENT, PRIMARY KEY | Unique identifier for the name record. |
| name_type_id | BIGINT UNSIGNED | NOT NULL, FOREIGN KEY to name_type(id) | References the type of the name. |
| value | VARCHAR(255) | NOT NULL | The actual name value (e.g., 'John', 'Smith'). |
| description | TEXT |  | Additional details about the name. |
| status_id | BIGINT UNSIGNED | FOREIGN KEY to status(id) | References the status of the name record. |
| created_by | BIGINT UNSIGNED | NOT NULL, FOREIGN KEY to user(id) | User who created the name record. |
| updated_by | BIGINT UNSIGNED | FOREIGN KEY to user(id) | User who last updated the record. |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Timestamp when the record was created. |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Timestamp of the last update. |

`map_document_file`

| **Column Name** | **Data Type** | **Constraints** | **Description** |
| --- | --- | --- | --- |
| id | BIGINT | AUTO_INCREMENT, PRIMARY KEY | Unique identifier for the mapping |
| document_id | BIGINT | NOT NULL, FOREIGN KEY to document(id) | References the document |
| file_id | BIGINT | NOT NULL, FOREIGN KEY to file(id) | References the file associated with the document |
| description | TEXT |  | Additional details about the document-file relationship |
| status_id | BIGINT | FOREIGN KEY to status(id) | Status of the mapping |
| created_by | BIGINT | NOT NULL, FOREIGN KEY to user(id) | User who created the mapping |
| updated_by | BIGINT | FOREIGN KEY to user(id) | User who last updated the mapping |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Timestamp when the record was created |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Timestamp when the record was last updated |
| Constraints |  | UNIQUE (document_id, file_id) | Ensures unique mappings between documents and files |

# **#7: TECH STACK REQUIREMENTS**

## Frontend Framework

### React ([React Website](https://react.dev/)) with Minimal UI Kit ([Minimal UI Kit Dashboard](https://mui.com/store/items/minimal-dashboard/?srsltid=AfmBOoqd99PYLOaakcg7uXzPgzCIGpGwiC7SdxzKqDnqRgjkDJyRzPy3))

- Purpose: Provides a responsive, dynamic user interface for both client and admin dashboard experiences. Offers a comprehensive design with multiple pre-built pages, components, and theming capabilities.
- Interactions: Communicates with backend services using REST or GraphQL APIs.

## Backend Frameworks

### Laravel (PHP) ([Laravel Website](https://laravel.com/))

- Purpose: Manages primary business logic, user data, and APIs.
- Interactions: Connects with the MariaDB database, Redis

## Operating System

### Debian (Linux OS) ([Debian Website](https://www.debian.org/))

- Purpose: Base OS for all containers, known for stability and compatibility.
- Interactions: Serves as the foundation for all services and applications.

## Web Server and Proxy

### NGINX ([NGINX Website](https://nginx.org/en/)) / NGINX Ingress Controller ([NGINX Ingress Documentation](https://docs.nginx.com/nginx-ingress-controller/))

- Purpose: Handles reverse proxy, load balancing, and SSL termination for frontend and backend services.
- Interactions: Routes traffic to the appropriate backend services.

## Primary Database

### MariaDB ([MariaDB Website](https://mariadb.org/))

- Purpose: Stores transactional data for core functionalities like policies, claims, and user details.
- Interactions: Communicates with Laravel and Redis.
- Laravel Migrations: Ensure table structures are consistent across environments.

## Caching

### Redis ([Redis Website](https://redis.io/))

- Purpose: Provides in-memory data caching to enhance response times and offload database requests.
- Interactions: Works with Laravel for frequently accessed data.

## Data Lake / Analytics

### LGTM Stack (Grafana LGTM Stack)

- Loki ([Loki Website](https://grafana.com/oss/loki/)): Log aggregation.
- Grafana ([Grafana Website](https://grafana.com/oss/grafana/)): Data visualization.
- Tempo ([Tempo Website](https://grafana.com/oss/tempo/)): Distributed tracing.
- Mimir ([Mimir Website](https://grafana.com/oss/mimir/)): High-availability metrics backend.
- Purpose: Manages log aggregation, monitoring, metrics, and tracing.
- Interactions: Provides logging and observability for backend services and infrastructure.

## Continuous Integration/Continuous Deployment (CI/CD)

### GitLab CI/CD ([GitLab CI/CD Website](https://about.gitlab.com/solutions/continuous-integration/))

- Purpose: Automates build, testing, and deployment workflows.
- Interactions: Works with Git, Docker, and Kubernetes.

### GitLab Container Registry ([GitLab Container Registry Website](https://docs.gitlab.com/ee/user/packages/container_registry/))

- Purpose: Stores Docker containers for CI/CD within GitLab’s ecosystem.
- Interactions: CI pipeline pushes built images to the GitLab registry; Kubernetes pulls images from the registry for deployments.

## Identity & Access Management (IAM)

### OAuth2 and JWT (via Laravel [Passport](https://laravel.com/docs/9.x/passport)/[Sanctum](https://laravel.com/docs/9.x/sanctum))

- Purpose: Provides secure authentication and access control.
- Interactions: Secures API interactions with OAuth2 and JWT tokens.

## Data Streaming & Event Processing

### Apache Kafka ([Kafka Website](https://kafka.apache.org/))

- Purpose: Manages event-driven data processing and high-throughput message streaming.

### Apache Zookeeper ([Zookeeper Website](https://zookeeper.apache.org/))

- Purpose: Required for managing Kafka cluster state if not using the newer KRaft mode.
- Interactions: Maintains configuration, naming, and synchronization for Kafka brokers.

## Container Orchestration

### Kubernetes ([Kubernetes Website](https://kubernetes.io/))

- Purpose: Manages and orchestrates all containers, ensuring scalability and availability.
- Interactions: Controls containers for backend, frontend, and supporting services.

## Networking and Load Balancing

### NGINX Ingress Controller for Kubernetes ([NGINX Ingress Documentation](https://docs.nginx.com/nginx-ingress-controller/))

- Purpose: Provides ingress routing, load balancing, and TLS termination within Kubernetes.
- Interactions: Directs external traffic to Kubernetes services.

## Monitoring & Logging

### Prometheus ([Prometheus Website](https://prometheus.io/)) and Grafana ([Grafana Website](https://grafana.com/))

- Purpose: Collects, monitors, and visualizes metrics data across services.
- Interactions: Integrates with Kubernetes and application services for real-time metrics.

## Secrets Management

### HashiCorp Vault ([Vault Website](https://www.vaultproject.io/))

- Purpose: Manages and secures sensitive data, such as API keys and passwords.
- Interactions: Supplies secrets to applications in a secure, encrypted manner.

## Backup and Disaster Recovery

### Velero ([Velero Website](https://velero.io/))

- Purpose: Backs up and restores Kubernetes resources and persistent volumes.
- Interactions: Works with Kubernetes to maintain business continuity.

## Digital Signatures

- Frontend:
    - Blade Templates: Templates are used to render user-friendly PDF previews.
    - Tailwind CSS: Provides modern styling for forms and signature displays.
- PDF Rendering/Generation:
    - [Barryvdh/Laravel-Snappy](https://github.com/barryvdh/laravel-snappy)
        - Converts HTML Blade templates to PDFs using wkhtmltopdf.
        - Blade templates are styled to ensure clarity and proper dimensions.
        - Two PDFs are generated: one for initials and another for the full signature.
        - PDFs are stored accordingly (e.g., references in PostgreSQL or MariaDB).

## Additional Components

### Docker ([Docker Website](https://www.docker.com/))

- Purpose: Provides containerization for microservices, enabling consistent packaging and deployment.
- Interactions: Each service has its own Dockerfile, CI/CD builds images.

### Docker Compose ([Docker Compose Website](https://docs.docker.com/compose/))

- Purpose: Simplifies local development by orchestrating multiple containers on a single host.
- Interactions: Local environment uses a docker-compose.yml to start containers with shared networks and volumes.

### NFS for Document and Image Storage ([NFSServerSetup](https://wiki.debian.org/NFSServerSetup))

- Purpose: Provides a shared file store for documents, images, and other static files.
- Interactions: Each container or microservice can mount the same NFS share for reading/writing files.

### SendGrid for Email Delivery ([SendGrid Website](https://sendgrid.com/en-us/solutions/email-api))

- Purpose: Provides a managed email delivery service for transactional and marketing emails (password resets, notifications, etc.).
- Interactions: Laravel integrates with SendGrid’s API or SMTP endpoint to send emails.

## How Everything Communicates: Full Technology Outline

### Base Operating System: Debian

- All services run on Debian-based images or VMs for stability, security, and consistency.
- Simplifies package management and ensures broad compatibility with Kubernetes deployments.

### Front-End: React + Minimal UI Kit + Tailwind

- React renders a dynamic UI for both end-user and admin dashboards.
- Minimal UI Kit provides pre-built components (cards, tables, forms) to accelerate UI development.
- [Tailwind](https://tailwindcss.com/docs/installation/framework-guides/laravel/vite) offers utility-first classes, ensuring rapid, consistent styling across the application.
- [Livewire](https://laravel-livewire.com/): For Blade-driven dynamic pages without heavy JS, can complement React in certain scenarios.
- Communicates with back-end services via REST or GraphQL APIs, sending JSON payloads or multipart form data.
- May subscribe to real-time events from Apache Kafka or WebSockets if required.

### Docker & Docker Compose

- Docker: Core containerization framework for packaging each microservice.
- Docker Compose: Used for local development or smaller-scale deployments, orchestrating multiple containers on one host.
- CI/CD pushes Docker images to the GitLab Container Registry or an external registry.

### GitLab CI/CD & GitLab Container Registry

- Automates builds, tests, and deployments.
- Docker images are pushed to the GitLab Container Registry under each project for versioned storage.
- Integrates with Kubernetes for automated rollouts upon successful builds.

### NFS for File Storage

- Provides a shared location for documents, images, and other static assets that multiple containers or pods can access.
- Useful for large file uploads, signature PDFs, or shared resources.

### SendGrid for Email Delivery

- Used by Laravel (or other back-end services) to send transactional emails, password resets, notifications, etc.
- Reduces the complexity of managing an internal SMTP server.

### Laravel

- Central hub for core business logic, user management, authentication (Sanctum), and PDF generation (Barryvdh/Laravel-Snappy).
- Connects to MariaDB for transactional data, uses Redis for caching, can publish/consume Kafka events.

### Apache Kafka + Apache Zookeeper

- Manages event-driven or streaming workloads.
- Zookeeper coordinates the Kafka cluster if not using newer KRaft-based mode.
- Ideal for high-throughput, real-time event processing.

### Kubernetes

- Orchestrates all containers, automatically scaling and restarting pods as necessary.
- Deployments can spread across on-prem servers and AWS EC2 nodes for redundancy.

### Databases & Storage

- MariaDB: Primary relational DB for transactional needs.
- NFS: Shared file storage for documents, images.
- Barryvdh/Laravel-Snappy: Generates PDFs for digital signatures or reports.

### Observability: LGTM Stack + Prometheus / Grafana

- Loki aggregates logs, Mimir/Prometheus collects metrics, Tempo traces requests.
- Grafana unifies data into dashboards, enabling real-time monitoring of system health.

### HashiCorp Vault

- Manages secrets (DB credentials, API tokens, encryption keys).
- Integrates with Kubernetes to inject secrets into pods at runtime.

### OAuth2 and JWT via Laravel Sanctum

- Token-based authentication for React clients or other services.
- Supports multiple concurrent sessions under a single account, each with distinct tokens.

### [Tailwind](https://tailwindcss.com/docs/installation/framework-guides/laravel/vite)

- offers utility-first classes, ensuring rapid, consistent styling across the application.

### [Livewire](https://laravel-livewire.com/)

- For Blade-driven dynamic pages without heavy JS, can complement React in certain scenarios.

### Velero

- Backs up Kubernetes resources (deployments, services, configmaps) and persistent volumes (MariaDB, PostgreSQL, etc.).
- Allows restoring entire namespaces or clusters in case of catastrophic failure.

## Overall Data Flow

1. React (UI) → NGINX / Ingress → Laravel (MariaDB / Redis).
2. Kafka for event-driven or real-time processing. Zookeeper to maintain Kafka cluster state.
3. NFS shares for document/image storage accessible by Laravel or other microservices.
4. Kubernetes manages container scaling and networking, distributing pods across on-prem.
5. Observability (Loki + Grafana + Tempo + Mimir/Prometheus) provides logs, metrics, and traces.
6. GitLab CI/CD automates building Docker containers, pushes them to the GitLab Container Registry, and deploys updates.
7. HashiCorp Vault injects secrets at runtime.
8. Velero offers backup/restoration of cluster resources and volumes.
9. SendGrid handles outbound email delivery for transactional or user notifications.

# **#8: GLOBAL REQUIREMENTS**

### 1.0 Identity & Access Management (IAM)

- **Sanctum for Authentication**
    - **Backend (Laravel)**
        - Uses Sanctum to issue and manage tokens/cookies for authenticated sessions.
        - Allows multiple user sessions under a single “account” or organization, with each user still tracked individually.
        - Composer handles Laravel package installations (e.g., Sanctum, Passport).
    - **Frontend (React / Livewire)**
        - Sends the Sanctum token with each request (e.g., cookie-based auth for browser sessions or token in headers for mobile apps).
        - Ensures each user within a shared account can remain logged in concurrently without interfering with others.

### 2.0 Database Migrations

- **Backend (Laravel)**
    - **MariaDB**:
        - Primary store for transactional records.
        - Migrations include foreign keys, constraints, and any new fields for business logic.
    - **Integration Tests**:
        - Use PHPUnit to confirm all migrations apply/rollback across environments.
        - Validate that the multi-DB setup (MariaDB and PostgreSQL) remains consistent and accessible.

### 3.0 Models & Relationships

- **Backend (Laravel)**
    - Eloquent models define fields/relationships for both MariaDB and PostgreSQL connections.
    - Redis to cache frequently accessed model data.
- **Frontend (React / Livewire)**
    - Match model fields in forms and UI elements.

Controllers & Routes

- **Backend (Laravel)**
    - RESTful endpoints (POST /images, GET /documents/{id}, etc.) for CRUD operations.
    - Can publish or consume events to/from Kafka if image processing or document workflows need event-driven coordination.
    - NGINX or the NGINX Ingress Controller routes external traffic into these endpoints in Kubernetes.
- **Frontend (React)**
    - Sends authorized requests (Sanctum token) to retrieve or update data.
    - Minimal UI Kit forms, tables, and modals tie directly to controller routes.

### 4.0 Validation & Data Handling

- **Laravel Validation Rules**
    - Enforce constraints (required, mimes for images, max size, etc.).
- **React / Livewire**
    - React to provide client-side checks (file type, text fields) using typical form libraries or built-in validations.
    - Livewire to apply server-side checks in near-real-time, returning validation errors without a full page reload.

### 5.0 Testing & Documentation Best Practices

- **Unit & Feature Tests**
    - **Laravel**: Use php artisan test (PHPUnit) for controllers, migrations, models.
    - **Playwright**:
        - End-to-end flows (upload image, check metadata, verify document record, etc.).
        - Component-level interactions (e.g., verifying form submission states, error messages).
- **Continuous Integration (CI)**
    - **GitLab CI/CD** runs tests automatically after code commits.
    - Ensures that any breakage in migrations, controllers, or front-end flows is caught early.
- **Code Documentation**
    - Inline PHPDoc for Laravel classes and methods, plus comments in React components.
    - A README or wiki explaining the multi-DB approach, environment variables, and how to run tests locally.

### 6.0 Project Structure & Organization

- **Folder Organization**
    - **Backend**: Controllers in app/Http/Controllers, models in app/Models, migrations under database/migrations.
    - **Frontend**: Organize React components, hooks, and utilities for clarity (e.g., src/components, src/services).
    - **Livewire**: Organize components within App/Http/Livewire for Blade-based dynamic UIs.
- **Consistent Naming**
    - Align model names with front-end references to minimize confusion.

### 7.0 Reusable Components & UI Consistency

- **Minimal UI Kit & Tailwind**
    - Pre-built UI elements (cards, modals, tables) combined with Tailwind utility classes for uniform design.
- **React & Livewire**
    - React: Small, focused components for reusability (e.g., image upload button, document list).
    - Livewire: Blade components for partial updates (e.g., inline editing, form wizards).

### 8.0 Performance Optimization

- **Backend**
    - Caching with Redis to handle frequent queries (e.g., top documents, recently uploaded images).
    - Offloads heavier tasks like background workers in Kubernetes.
- **Frontend (React)**
    - Code splitting (React.lazy) to load only necessary components.
    - Memoization (React.memo, useMemo) to prevent re-render storms in data-heavy dashboards.
- **Kubernetes & NGINX**
    - Auto-scaling pods based on CPU/memory usage, ensuring high availability.
    - Routes traffic efficiently, handles SSL termination.

### 9.0 State Management

- **Frontend (React)**
    - Local state in individual components for small tasks (e.g., form inputs).
    - React Context for shared state across multiple screens if needed.
- **Livewire**
    - Maintains server-side state, sending updates to the Blade views seamlessly.
    - Ideal for smaller projects or dashboards that don’t require a full SPA approach.

### 10.0 Testing & QA

- **Unit Testing**
    - **Laravel**: Models, validations, relationships, migrations (PHPUnit).
    - **Playwright**: Single component interactions (form submit, error states).
- **Integration & E2E Testing**
    - **Playwright** for full flows (user login, image upload, doc creation, logout).
    - Verify that images are stored on the server, with corresponding doc records in MariaDB.

### 11.0 Accessibility

- **ARIA & Keyboard Navigation**
    - Label all interactive elements in React or Blade templates.
    - Ensure tab ordering is logical for forms, navigation, and modals.
- **Color Contrast & Text Size**
    - Tailwind utility classes help maintain WCAG-compliant contrast.
    - Keep text and icons legible for visually impaired users.

### 12.0 Security Considerations

- **Sanitize User Inputs**
    - Validate image files (size, type, resolution) before storing.
    - Apply Laravel’s validation rules for text fields to prevent XSS or SQL injection.
- **HTTPS & Secure Headers**
    - NGINX terminates SSL;
    - set secure headers (Content-Security-Policy) in production.
- **Multiple Concurrent Sessions**
    - Sanctum allows multiple users to log in under a single “account” or organization ID, each with distinct tokens.
    - Activities are tracked individually for auditing and logging.

### 13.0 Error Handling & Logging

- **Local Logging & Loki**
    - Laravel writes logs to local files while also shipping them to Loki for centralized analysis.
- **Frontend**
    - Display user-friendly error messages or toast notifications upon failed API calls.

### 14.0 Documentation

- **README & Wiki**
    - Step-by-step guide on installing Composer packages, preparing .env files for MariaDB connections, and running migrations.
    - Outline image storage approach (PostgreSQL) vs. doc references (MariaDB).
- **Inline Comments & Docblocks**
    - Laravel classes, React components, and Livewire logic should be well-commented for maintainability.
    - Describe complex relationships or multi-database logic in docblocks for clarity.

### 15.0 Version Control & DevOps Integration

- **GitLab CI/CD**
    - Runs back-end (PHPUnit) and front-end (Playwright) tests automatically.
    - Deploys container images to Kubernetes upon successful builds.
- **Kubernetes**
    - Orchestrates Laravel, MariaDB, Redis, etc.

### **16.0 NFS File Store Requirement**

1. **Purpose and Scope**
    - The system must include a dedicated **NFS (Network File System)** for storing and sharing all system files across multiple application servers and services.
    - This centralized file store will handle assets such as application uploads, logs, backups, images, and other static or dynamic files required by various components of the platform.
2. **Technical Specifications**
    - **NFS Version**: Must support NFSv4 (or higher) to take advantage of improved security and performance features.
    - **Storage Capacity**:
        - Initial capacity sized to accommodate the largest expected data requirements (e.g., 1–5 TB).
        - Scalable storage solution (either by adding more disk or extending the NFS volume) to handle future growth.
    - **Network Throughput**:
        - Capable of sustaining read/write operations for all active servers and applications.
    - **Permissions & Security**:
        - Granular read/write/execute permissions for different user groups or microservices.
        - Secure mounting options (e.g., sec=sys or sec=krb5 if Kerberos-based authentication is required).
    - **Resilience & Availability**:
        - High-availability NFS server or cluster (e.g., multiple NFS nodes in active-passive mode).
        - Regular backups or snapshots to guard against data loss.
    - **Mount Points**:
        - Consistent mount paths across all application nodes (e.g., /mnt/nfs/files), ensuring uniform directory structures.
        - Automatic mounting upon system boot or container startup (e.g., systemd service or Kubernetes Persistent Volume/Persistent Volume Claim if in a containerized environment).
3. **Integration with Platform Components**
    - **Applications & Services**:
        - All microservices store and retrieve static or user-generated files in the NFS mount.
        - Shared logs or artifacts can be placed on NFS so all nodes have access to the same data.
    - **Kubernetes**:
        - NFS-based **Persistent Volume (PV)** and **Persistent Volume Claims (PVC)** can be created to expose the file system to pods running in the cluster.
        - Ensures stateful applications can read/write data without requiring each node to maintain a local copy.
    - **CI/CD**:
        - GitLab CI/CD workflows may copy artifacts or deployment bundles to the NFS share for distribution across multiple environments.
        - Facilitates a single source of truth for build outputs, logs, or intermediate artifacts.
    - **Backup & Disaster Recovery**:
        - **Velero** or other backup tools can snapshot the NFS volume.
        - In the event of node failure, data remains intact on the NFS server, enabling quick redeployments.
4. **Maintenance & Monitoring**
    - **Monitoring Tools**:
        - Grafana or Prometheus exporters for NFS can track disk usage, I/O throughput, and latency.
        - Alerts triggered if capacity exceeds threshold (e.g., 80% utilization) or throughput drops below acceptable levels.
    - **Maintenance Windows**:
        - Procedures for safely unmounting the NFS share before upgrades or filesystem checks.
        - Scheduled tasks to prune old logs, archives, or unused assets, keeping storage usage in check.
    - **Security & Auditing**:
        - Access logs for each mount operation and file read/write (if required).
        - Integration with LDAP/AD or other authentication systems for user-level access control.
5. **Performance Considerations**
    - **Caching & Locking**:
        - Proper NFS client caching to reduce round-trip latency for frequent file reads.
        - File locking mechanisms (advisory or mandatory) if multiple services may write to the same file concurrently.
    - **Compression / De-duplication**:
        - Optional storage vendor-specific features to reduce disk usage if large redundant files are expected (e.g., container images, logs).

### 17.0 High-Level Functional Requirements

1. Centralized Activity Logging
    - Every create, read, update, delete (CRUD) or additional operation (trash, restore, process, etc.) must create a new record in an action table.
    - This action record captures essential metadata such as action_type_id, descriptive text, status, timestamps, and user references—including cases where the user reference is a “system” user or a “scheduler” user rather than a real individual.
2. Linking Actions to Main Entity
    - A map_ENTITY_action table (e.g., map_document_action) links a single action to the specific entity record it affects.
    - For instance, if the main entity is a document, a row in map_document_action will store the document_id and the corresponding action_id.
3. User Accountability
    - All actions must store who performed them (created_by, updated_by), ensuring that every modification can be traced to a specific user or process.
    - System-driven or scheduled operations also use a user reference but point to a system user or scheduler user in the users table. This maintains a unified approach—every action has a user_id, regardless of whether it’s a real person or an automated account.
4. Action Type Classification
    - The action_type table holds the actual operation names—e.g., create, read, update, delete, trash, processed, etc.
    - The action_type_id in the action table references which operation occurred, but does not indicate whether it was manual, system-driven, or scheduled.
    - Who performed it (manual user, system user, or scheduler user) is determined by the user_id—not by the action type.
5. Status and Lifecycle
    - Each action, action type, and mapping entry may also reference a status_id to reflect its lifecycle state (e.g., active, completed, archived).
    - This allows the system to track if an action or mapping record is still relevant or if it’s been superseded.
6. Detailed Change Description
    - The action table’s description field should store what changed in the main entity. For example:
        - “Changed title from ‘Draft Policy’ to ‘Final Policy’.”
        - “Document #123 was moved to the trash.”
    - This ensures each action record stands alone as a mini-audit log, providing clarity about exactly what happened.

### 18.0 Workflow Requirements

1. Create / Insert
    - action: Insert a new record indicating a “create” action and relevant details (e.g., which fields were set).
    - map_ENTITY_action: Insert a record linking the newly created entity to the action.
    - Entity: Insert the entity row (e.g., a new row in document).
    - If the creation is system-driven, the action’s created_by references the system user.
2. Read / View
    - Insert a “view” or “accessed” action in the action table and link it via map_ENTITY_action.
3. Update
    - action: Insert a new action record for “update,” describing which fields changed (e.g., old vs. new values).
    - map_ENTITY_action: Create a new mapping row, referencing the updated entity.
    - Entity: The entity row gets updated with the new data.
    - If the update is triggered by a scheduler user, set user_id to that scheduler’s account.
4. Delete / Trash / Process
    - action: Insert a record for a “delete,” “trash,” or “process” action, detailing the operation.
    - map_ENTITY_action: Insert a row referencing the entity and the action.
    - Entity: Reflect the change in the entity (e.g., marking it as “trashed,” removing it, or setting a “processed” flag).
    - The action table’s created_by might point to a system or scheduler user if it’s an automated job.
5. Atomic Transactions
    - Ideally, these inserts and updates happen within a single transaction so that either all records are written or none are, preventing partial writes.

### 19.0 Table Relationships & Requirements

1. action_type
    - Stores the names of actions like create, read, update, delete, trashed, processed, etc.
    - Does not specify whether the action is manual or automated; that’s determined by the user performing the action.
    - A status_id and user references (created_by, updated_by) may track the lifecycle of each action type definition.
2. action
    - References action_type_id to identify which operation was performed (CRUD, trash, process, etc.).
    - Stores description with details of what exactly changed.
    - Has user references (created_by, updated_by) to link the action to a specific person (manual user) or automated account (system, scheduler).
    - A status_id indicates the action’s current validity or completion state.
3. map_ENTITY_action (e.g., map_document_action)
    1. Entity reference: e.g., document_id for a document entity.
    - action_id: Links back to the action just recorded.
    - Optionally includes a separate description or status_id for the mapping itself, if needing to track the mapping’s lifecycle.
    - Also records created_by, updated_by to capture who linked the action with the entity (often the same user who performed the action).

### 20.0 Application & Business Logic

- Whenever an entity is modified (create/update/delete), the application layer triggers an insert in action and map_ENTITY_action, plus the corresponding change in the entity table.
1. Use Eloquent Model Observers
    - In Laravel, define an Observer class for each primary model (e.g., DocumentObserver) that listens to Eloquent events: creating, created, updating, updated, deleting, deleted, etc.
        - For example, when a Document model is created (created event), automatically insert a row in the action table (denoting “create”) and a corresponding row in map_document_action.
2. Advantages of Observers
    - Decoupled Logic: Keeps audit logging out of controllers or service layers, maintaining a cleaner codebase.
    - Automatic: Any time someone uses Eloquent to create/update/delete the model, the observer runs—covering both manual user actions (via Laravel Sanctum) and automated ones (system or scheduler accounts).
3. Sample Observer Workflow
    1. *creating/created Event (for Create)*
        - action Table: Insert an action row with action_type_id = ID referencing “create,” user references (created_by = the user’s ID or system user’s ID), and a description of what’s being created.
        - map_DOCUMENT_action Table: Insert a row linking the newly created document_id to the action_id.
        - document Table: The standard Eloquent insert completes as usual.
    2. *updating/updated Event (for Update)*
        - Compare old vs. new attributes (Laravel provides getOriginal(), getDirty() methods) to build a meaningful “changed fields” description.
        - action Table: Insert an action row with action_type_id referencing “update.”
        - description might say “Changed title from ‘Draft’ to ‘Final’.”
        - map_DOCUMENT_action Table: Insert a row linking the document_id to that action.
        - document Table: Proceed with the Eloquent update.
    3. *deleting/deleted Event (for Delete or Trash)*
        - action Table: Insert an action row referencing “delete” or “trash.”
        - map_DOCUMENT_action Table: Map the document_id to this new action.
        - document Table: Perform the actual soft/hard delete.
4. DB Transactions
    - To avoid partial writes, wrap each observer operation in a DB transaction.
        - For example, in the creating or updated event, manually begin a transaction via DB::transaction(...) if logic is complex. Otherwise, rely on automatic transaction management in the service layer.
5. Handling System or Scheduler Actions
    1. User Reference
        - In the Observer logic, check the “user” context.
        - If it’s triggered by a system or scheduler script (e.g., a command in Kubernetes or a queue job), set created_by or updated_by to a system user ID or scheduler user ID.
    2. Action Type
        - The action_type still references what operation occurred (create, update, etc.). The fact it’s system-driven is inferred from the user ID (system or scheduler).

### 21.0 Integration with Other Stack Components

1. Kubernetes & NGINX
    - No direct changes needed in NGINX or the Ingress Controller, aside from ensuring app routes are accessible for the relevant tasks.
2. Redis Caching
    - Updates cause invalidation of cached data.
    - Either hook that logic into the same Observer or in a dedicated caching layer.
3. Kafka (Optional)
    - If certain actions (like “processed”) need to publish messages to Apache Kafka for asynchronous processing, do so in the Observer after successfully inserting the action row.
    - E.g., “When a document is updated with status = ‘approved,’ publish a ‘document-approved’ event to Kafka.
4. GitLab CI/CD & Vault
    - CI/CD and HashiCorp Vault handle deployment pipelines and secret management. The observer approach remains unchanged; environment variables and DB credentials remain consistent.
5. Logging & Observability
    - Loki, Grafana, and Tempo capture logs, metrics, and traces. Observer-based logs (e.g., “Document #123 created via system user #1”) can be output as standard Laravel logs or additional metadata, aiding tracing and debugging.
6. Audit & Security
    - Only authorized users can perform certain action types (e.g., only admins can hard-delete).
    - System or Scheduler user accounts exist for automated tasks (e.g., nightly jobs or system-level maintenance).
    - Each user action is fully auditable, storing who did it and when.
7. Performance Considerations
    - The tables can grow large if many actions are logged. An archiving or partitioning strategy might be necessary for high-volume systems.
    - Index frequently queried columns (e.g., entity_id, action_id, created_by) for performance.

### 22.0 High Level Architecture

Below is an outline of requirements for implementing Replicated Microservices + Databases per Tenant within the existing tech stack. Each tenant (i.e., each client) receives its own dedicated microservices, database(s), and Kubernetes namespace, providing strong data and resource isolation.

1. Dedicated Namespace per Tenant
    - Each tenant operates within a distinct Kubernetes namespace, hosting its own copies of core services.
    - Resource usage (CPU, memory) is managed at the namespace level, allowing each tenant’s usage to be monitored and scaled independently.
2. Separate Databases
    1. Each tenant runs its own MariaDB instance (or cluster), isolating data and ensuring one tenant’s queries do not impact another.
3. Microservices per Tenant
    - Containers are replicated for each tenant.
    - The tenant’s front-end (React with Minimal UI Kit + Tailwind, or Livewire components) points to that specific tenant’s microservices.

### 23.0 Deployment & Scalability

1. Kubernetes & Ingress
    - Each namespace uses NGINX Ingress rules to route traffic to that tenant’s microservices.
    - Domain-based or path-based routing (e.g., tenantA.example.com vs. tenantB.example.com) ensures requests go to the correct namespace.
2. Horizontal Scaling
    - If a tenant’s usage grows, scale up the respective pods (Laravel, Redis, etc.) in that namespace without affecting other tenants.
    - Kubernetes HorizontalPodAutoscaler and resource quotas to fine-tune how each tenant’s microservices react to load spikes.
3. Resource Efficiency 
    - Underlying infrastructure (Kubernetes masters, nodes, logging, and monitoring) is shared, while each tenant’s application and DB remain isolated.
    - This design streamlines management of multiple clients within a single cluster environment.

### 24.0 Data & Security

1. Isolation of Data
    - Each tenant’s MariaDB instance keeps data separate, reducing risk of cross-tenant data leakage.
    - HashiCorp Vault manages unique credentials/secrets for each tenant’s DB and microservices.
2. Access & Authentication 
    - Laravel Sanctum (or Passport) is deployed per tenant copy of Laravel, ensuring each has a separate user base and tokens.
    - System and Scheduler user accounts will be replicated per tenant.
3. Backups & Disaster Recovery
    - Velero to snapshot each namespace’s resources and volumes individually.
    - Database backups happen per tenant, ensuring restore operations are tenant-specific and do not affect other namespaces.

### 25.0 Observability & Logging

1. Logging & Metrics
    - Loki collects logs from each tenant’s pods, labeled by namespace.
    - Prometheus / Mimir monitors CPU, memory, request counts for each tenant, exposing separate metrics.
    - Tempo traces request flows within a single tenant’s microservices or across them if needed.
2. Dashboards & Alerts
    - Grafana to filter dashboards by namespace, giving ops teams a clear view of each tenant’s health.
    - Alerts (e.g., resource usage thresholds) to be scoped to a particular tenant’s microservices.

### 26.0 CI/CD & Automation

1. GitLab CI/CD
    - A single pipeline builds Docker images for microservices.
    - At deploy time, the pipeline spawns or updates resources in each tenant’s namespace, pulling secrets from Vault for DB credentials or environment variables.
2. Automated Provisioning
    - When onboarding a new tenant, a Kubernetes namespace is created, plus dedicated MariaDB resources.
    - Scripts or Helm charts to apply the microservices, Ingress rules, and DB credentials automatically.
3. Version Control & Rollouts
    - Each microservice can be pinned to a stable version across all tenants or rolled out incrementally to specific tenants first.
    - Canary or blue-green deployments can test new features on a subset of tenants without risking others.

### 27.0 Performance & Scalability

1. Caching & Message Streaming
    - Separate Redis.
    - Apache Kafka usage to be segmented per tenant (unique topics or partitions), or shared with tenant-based filtering.
2. Autoscaling Best Practices
    - Monitor each tenant’s load using Prometheus.
    - Scale pods in each namespace independently, allowing heavier tenants to scale up while smaller ones run with minimal resources.

Summary

By giving each tenant its own Kubernetes namespace, microservices, and dedicated databases, you achieve:

- Clear isolation of data and computing resources for each client.
- Scalability at a per-tenant level, letting busier tenants handle more traffic without impacting others.
- Effective re-use of shared Kubernetes infrastructure, while maintaining security boundaries via separate namespaces.

### 28.0 Microservice Docker Requirements

Below is an outline of requirements for building Docker images for each microservice within a single CI/CD pipeline. This approach ensures that every service has its own Dockerfile and can be orchestrated using docker-compose or equivalent tooling, with integration into GitLab CI/CD.

1. Dedicated Dockerfile per Microservice
    - Each microservice has a Dockerfile in its repository or a designated folder. The Dockerfile specifies the base image, required dependencies, and environment configurations.
2. docker-compose Integration 
    - A docker-compose.yml (or multiple compose files) to define how these services interact locally or in staging. Each microservice is a separate service in docker-compose, referencing its specific Dockerfile.
3. Automated Build Process
    - GitLab CI/CD automates the build of each Docker image upon code changes. Relevant secrets and environment variables to be sourced from HashiCorp Vault or other secure storage.
4. Version Tagging & Registry
    - Successfully built images are tagged with version labels (such as commit SHAs or semantic version numbers) and pushed to a container registry. Each microservice has distinct identifiers for tagging and retrieval.

### 29.0 Dockerfile Requirements

1. Microservice-Specific Dockerfiles
    1. Laravael Dockerfile:
        - Include a PHP-FPM or official PHP base image, installation of Composer dependencies, and copying of application code.
2. Best Practices
    1. Multi-Stage Builds
        - Compile assets and dependencies in one stage, then copy the resulting artifacts into a minimal runtime image.
    2. Security
        - Employ a non-root user and minimal permissions.
    3. Caching
        - Utilize Docker layer caching by placing dependency installations before copying the main application code.
    4. Environment Variables
        - Store sensitive data in secrets (Vault/CI variables) rather than hardcoding them.
3. Version Control 
    - Each Dockerfile resides within the relevant microservice folder or repository. Any changes to the Dockerfile trigger a new build for that specific microservice.

### 30.0 docker-compose Requirements

1. Multiple Service Definitions
    - A docker-compose.yml to define each microservice, pointing to its Dockerfile. It will also include local development dependencies (such as databases or caches).
2. Shared Services
    - If multiple microservices share a local database or other external service, those will be listed in the same docker-compose file or an override file.
    - Employ network aliases and environment variables to help each microservice connect properly.
3. Local vs Production 
    - docker-compose to be used for local development or staging environments. Production deployments often use Kubernetes or Helm charts, but rely on the same Docker images.

### 31.0 CI/CD Pipeline Requirements

1. Pipeline Stages
    1. Build
        - Runs docker build commands for each microservice.
    2. Test
        - Runs tests by spinning up services with docker-compose or directly calling test frameworks.
    3. Deploy
        - Pushes built images to GitLab Container Registry, tagging them with the commit SHA or version number.
2. Service-Specific Jobs
    - Each microservice has a distinct job in the .gitlab-ci.yml that handles building and pushing its Docker image. This ensures modular, independent builds.
3. Environment Variables & Secrets
    - Sensitive data such as API keys to be retrieved from Vault or GitLab’s encrypted variables. Non-sensitive environment variables to be placed in .env files or within the pipeline settings.
4. Version Tagging & Promotion
    - Images are tagged (e.g., myservice:1.2.3 or myservice:<commit-sha>) to differentiate stable releases from development builds. This allows selective deployment to staging or production.

### 32.0 Deployment & Orchestration

1. Kubernetes Manifests or Helm Charts
    - The images produced by the pipeline are referenced in Kubernetes deployments or Helm charts. Each microservice has a separate deployment configuration using environment variables from ConfigMaps and Secrets.
2. Namespace & Tenant Considerations
    - The same Docker images are reused in different namespaces with tenant-specific environment variables and/or secrets.
3. Integration with Observability
    - The Docker images include instrumentation for Prometheus, Loki, and Tempo, ensuring consistent logging and monitoring in production.

### 33.0 Data Services (Databases, Caching, Streaming)

1. MariaDB / PostgreSQL
    - run as a Kubernetes StatefulSet or as standalone VMs/containers on each physical server.
    - Replication or clustering can ensure that if one node fails, the database remains accessible on the others.
    - Persistent Volumes (PV) on local SSD or network storage (Ceph, Gluster, or cloud block storage).
2. Redis
    - For caching or session storage, run a Redis cluster or a Redis primary/replica approach.
    - Could be deployed as pods with persistent volumes or as an external service.
    - Ensure each node is placed in a different physical location (Physical Server A, B, or Cloud) to survive a server loss.
3. Kafka
    - Run at least 3 brokers across these three servers.
    - Each broker has its own persistent storage (SSD recommended).
    - Zookeeper also needs 3 instances for a stable quorum, typically co-located with each Kafka broker or separate pods.

### 34.0 Supporting Services

1. HashiCorp Vault
    - Cluster Vault with at least 3 instances (each on a different node) or rely on an external Vault cluster.
    - The physical servers and cloud server each can host one Vault replica.
    - This ensures availability if one server or site fails.
2. NGINX Ingress
    - Deployed as a DaemonSet or Deployment across all worker nodes for load balancing incoming traffic.
    - Each server can run an NGINX Ingress instance, and external DNS routes traffic to any of the available nodes.
3. Monitoring & Logging
    - Grafana, Prometheus, Loki, Tempo, and Mimir can be run as pods across all three nodes.
    - Persistent volumes for monitoring data may be placed on local SSD (for performance) or network storage (for easier backup).
4. Velero
    - Backs up cluster resources and persistent volumes.
    - Scheduled backups can store data on a cloud object storage bucket or another offsite location.

### 35.0 Networking & Load Balancing

1. Network Connectivity
    - Each physical server connects to the local LAN at 1–10 Gbps.
    - The cloud server communicates over VPN or secure tunnel to join the same Kubernetes cluster.
2. External Access
    - DNS or a cloud load balancer can direct traffic to the Ingress controllers on each server.
    - If one server is offline, traffic still flows to the remaining nodes.

### 36.0 Authentication, User Groups & Permissions Architecture

This architecture ensures role-based access control (RBAC) and feature-based permissions while allowing for flexibility in user and producer management. The system is designed to handle authentication, user classification, and fine-grained permissions for both internal users (e.g., CSRs, underwriters, claims handlers) and external users (e.g., producers, insureds).

### 36.1 Authentication & User Management

1. User Table
    1. Purpose: Stores user account details for authentication and identity management.
    2. Key Fields:
        - id – Unique identifier for the user.
        - name – Full name of the user.
        - email – User's email (used for login).
        - password – Encrypted password.
        - user_type_id – Links the user to a user type.
        - status – Active, inactive, or suspended.
        - audit – Tracks user creation and modifications.
2. User Type Table
    1. Purpose: Defines different types of users in the system (e.g., insured, producer, internal staff).
    2. Key Fields: 
        - id – Unique identifier.
        - name – Name of the user type (e.g., Insured, Producer, Underwriter, Claims Adjuster).
        - description – Brief description of the user type.
3. User Group Table
    1. Purpose: Groups users based on their job function or level (e.g., CSR, manager, underwriter).
    2. Key Fields:
        - id – Unique identifier.
        - name – Name of the user group (e.g., CSR, Manager, Underwriter).
        - description – Brief explanation of the group's role in the system.
4. Map User to Group Table (map_user_group)
    1. Purpose: Maps users to multiple groups, allowing for flexible access control.
    2. Key Fields:
        - user_id – Links to a specific user.
        - user_group_id – Links to a user group.

### 36.2 Permissions & Feature Access

1. Feature Table
    1. Purpose: Defines system functionalities and modules that can have restricted access.
    2. Key Fields:
        - id – Unique identifier.
        - name – Name of the feature (e.g., Policy, Claims, Payments, Reports).
        - feature_type_id – Links to the feature type table.
2. Feature Type Table
    1. Purpose: Categorizes system features (e.g., System Module, Third-Party API).
    2. Key Fields:
        - id – Unique identifier.
        - name – Category of the feature (e.g., Core System, API Integration).
3. Permission Table
    1. Purpose: Defines actions users can perform on a feature (e.g., view, update, quote, bind).
    2. Key Fields:
        - id – Unique identifier.
        - name – Permission type (e.g., view, edit, delete, bind).
        - description – Explanation of the permission.
4. Map User Group to Feature Permissions (map_user_group_feature_permission)
    1. Purpose: Maps user groups to features and assigns permissions to them.
    2. Key Fields:
        - user_group_id – Links to a user group.
        - feature_id – Links to a system feature.
        - permission_id – Defines what actions can be performed on the feature.
    3. Example Use Case:
        - Underwriters (user group) may have view, update, and bind permissions for the policy module (feature).
        - CSRs (user group) may have view and update permissions but not bind permission.

### 36.3 Producer Management and Access Control

1. Producer Table
    1. Purpose: Stores producer-specific details and links them to their assigned users.
    2. Key Fields:
        - id – Unique identifier.
        - producer_code_id – Links to a specific producer code.
        - name – Producer's name.
        - type_id – Links to the producer type table.
2. Producer Code Table
    1. Purpose: Defines producer codes with their unique identifiers.
    2. Key Fields:
        - id – Unique identifier.
        - code – Unique producer code assigned by the system or carrier.
        - description – Additional details about the code.
        - status – Active or inactive.
        - audit – Tracks changes.
3. Producer Type Table
    1. Purpose: Defines different categories of producers (e.g., independent agency, direct writers).
    2. Key Fields:
        - id – Unique identifier.
        - name – Type of producer (e.g., Independent Agent, MGA, Direct).
4. Map User to Producer Table (map_user_producer)
    1. Purpose: Allows users to be linked to one or more producers, granting them access to producer-related data.
    2. Key Fields: 
        - user_id – Links to a user.
        - producer_id – Links to a producer.
    3. Example Use Case: 
        - A producer's assistant (user) can be mapped to multiple producer entities they manage.
        - A regional manager can be mapped to multiple independent agencies they oversee.
5. Producer Group Table
    1. Purpose: Organizes producers into groups for better management.
    2. Key Fields: 
        - id – Unique identifier.
        - producer_group_type_id – Links to a producer group category.
        - code – Unique identifier for the group.
        - description – Summary of the group's purpose.
6. Producer Group Type Table
    1. Purpose: Defines the types of producer groups (e.g., regional agents, national producers).
    2. Key Fields: 
        - id – Unique identifier.
        - name – Type of producer group (e.g., Regional, National).
7. Map Producer to Producer Group Table (map_producer_group)
    1. Purpose: Assigns producers to multiple producer groups.
    2. Key Fields: 
        - producer_id – Links to a producer.
        - producer_group_id – Links to a producer group.
    3. Example Use Case:
        - A regional producer (producer_id) may belong to a national producers group (producer_group_id).
        - A national aggregator may oversee multiple regional producer groups.
8. Map Producer Group to Feature Permissions (map_producer_group_feature_permissions)
    1. Purpose: Defines which features producer groups can access and their permissions.
    2. Key Fields:
        - producer_group_id – Links to a producer group.
        - feature_id – Defines what part of the system is accessible.
        - permission_id – Determines the allowed actions (view, update, bind).
    3. Example Use Case:
        - Regional producers (producer group) may have view and quote access to policy records (feature).
        - National agencies may have bind and issue permissions in addition to view access.

### 36.4 How Authentication and Permissions Work Together

1. User Logs In
    - Authenticated via AuthenticationManager (OAuth2/JWT).
    - User type is identified via the user_type table.
2. User’s Role is Determined
    - User is linked to one or more user groups via map_user_group.
3. Feature Access is Evaluated
    - System checks map_user_group_feature_permission for allowed features and permissions.
    - If a user is associated with a producer, their access is further validated via map_user_producer.
4. Actions Are Controlled
    - Users can perform only the actions they have permissions for (view, update, bind).
    - Additional validation is performed for producers to ensure they operate within allowed producer groups.

### 37.0 Locking Workflow with Centralized Logging and Action Tracking

1. Centralized Locking and Activity Logging
    - Every lock and unlock operation, as well as create, read, update, delete (CRUD) and additional operations (trash, restore, process, etc.), must create a new record in the action table.
    - The action record must capture essential metadata, including:
        - action_type_id – Identifies the type of action performed.
        - description – Provides a summary of the action taken.
        - status_id – Indicates whether the action is active, expired, or released.
        - timestamps – Records when the action occurred.
        - user references – Identifies who performed the action.
    - User references must be recorded for all actions, whether performed by:
        - A real user (user_id).
        - A system process (system_user_id).
        - A scheduler job (scheduler_user_id).
    - Each lock operation must be logged in the lock table to ensure that every locked record has a corresponding action record, which tracks:
        - The type of lock applied (lock_type_id).
        - The entity that was locked (entity_type_id).
        - The user responsible for the lock (created_by, updated_by).
2. Linking Locks and Actions to Records
    - action.record_id must link each action to the specific record it affects.
    - map_lock_action must ensure that every lock is tied to an action, maintaining consistency between the locking mechanism and the audit log.
3. User Accountability and Permissions
    - All locks and actions must store who performed them (created_by, updated_by), ensuring full traceability.
    - System-driven or scheduled operations must use a user reference that points to either a system user or a scheduler user in the user table.
    - Access to locked records must be controlled based on user roles:
        - Administrators or managers may override a lock.
        - Regular users may only view locked records in read-only mode.
4. Lock Types and Status Management
    - Locks can be temporary or system-enforced. The lock_type table defines different lock types, including:
        - User Lock – Applied when a user starts editing a record.
        - System Lock – Applied automatically by a system process (e.g., data synchronization).
    - The status_id field in both lock and action tables determines whether the lock is:
        - Active – The record is currently locked.
        - Expired – The lock has timed out.
        - Released – The lock was manually removed.
5. Detailed Change Description in Action Logs
    - Each action record must store a clear, meaningful description of what changed in the locked entity.
    - Examples:
        - Lock Acquired: "User #5 locked Policy #123 for editing."
        - Lock Released: "System unlocked Claim #45 due to timeout."
        - Field Update: "Updated policy status from ‘Pending’ to ‘Active’."
    - The action table serves as an independent audit log, maintaining a history of lock activity and record changes.

### 37.1 Workflow Requirements

1. Lock Creation/Insert
    1. Check for Existing Lock
        - Before applying a lock, verify if the record is already locked (record_id in lock).
        - If an active lock exists (status_id = Active), prevent other users from editing.
    2. Insert Lock Record
        - Create a new row in lock, specifying:
            - lock_type_id – The type of lock applied.
            - entity_type_id – The entity category being locked.
            - record_id – The specific record being locked.
            - created_by – The user who initiated the lock.
    3. Insert Action Record
        - Create a corresponding action record, with action_type_id referencing lock creation.
    4. Map Lock to Record
        - Insert a row in map_lock_action, linking the lock and action records to the specific record.id.
        - If the lock is system-driven, the created_by field must reference the system user.
2. Read/View a Locked Record
    - Check if the record is locked in lock.
    - If locked, restrict editing and allow only read-only access.
    - Log an action in the action table, indicating that the record was accessed while locked.
3. Update a Locked Record
    1. Verify Lock Ownership
        - If the user attempting to update the record does not own the lock, deny the update.
        - If the user owns the lock, allow the update.
    2. Insert Action Record
        - Log an update action, capturing field changes (old_value, new_value).
    3. Unlock Automatically Upon Save
        - Release the lock by updating status_id in lock.
        - Log an unlock action in the action table and map_lock_action table.
4. Delete/Trash/Process a Locked Record
    1. Verify Lock Status
        - If the record is locked by another user or system process, prevent deletion.
        - If the user owns the lock, allow the delete/trash operation.
    2. Log a Delete or Trash Action
        - Insert a row in action, specifying action_type_id as "delete" or "trash".
    3. Update lock to mark it as inactive
        - Update lock to mark it as inactive.
5. Automatic Lock Expiration & Release
    1. Scheduled Cleanup Job
        - A background process will periodically check lock for expired locks.
        - If created_at is older than 30 minutes, update status_id to "expired".
        - Log an unlock action with the description: "Lock auto-released due to timeout."
    2. User-initiated Unlock
        - Certain users (e.g., administrators) can manually unlock records.
        - Logs an override action in action, specifying who forcefully removed the lock.

### 37.2 Table Relationships & Data Structure

1. Entity Lock Table (lock)
    - Tracks active and expired locks on records.
    - Links to user for accountability.
2. Lock Type Table (lock_type)
    - Defines different lock mechanisms, including:
        - User lock (manual).
        - System lock (automated).
3. Entity Type Table (entity_type)
    - Categorizes entities that can be locked, such as:
        - Policy
        - Claim
        - User
4. Action Table (action)
    - Stores every event in the system, including:
        - Locking and unlocking actions.
        - Record modifications.
        - System-driven updates.
5. Mapping Table (map_lock_action)
    - Links lock records to action records for audit tracking.

### 37.3 Business Logic & Application Layer

1. Eloquent Observers for Automatic Logging
    - Observers track Eloquent events (creating, updating, deleting).
    - When a model is updated, an action is automatically logged.
2. Transactional Integrity
    - Locks, actions, and entity updates must be wrapped in a database transaction to prevent partial writes.
3. Role-Based Lock Overrides
    - Certain user roles (e.g., managers, administrators) can override locks when necessary.

### 38.0 Microservice Architecture

1. AuthenticationManager
    1. Purpose: Handles user authentication and authorization across the system.
    2. Key Responsibilities:
        - OAuth2/JWT-based authentication via Laravel Sanctum.
        - Role-Based Access Control (RBAC) for different user roles (agents, insureds, underwriters, administrators).
        - Multi-Factor Authentication (MFA) for enhanced security.
    3. Example Interactions:
        - Used by all services to authenticate and authorize users.
        - Works with **ProducerPortal**, **InsuredPortal**, and **AccountingManager** for user-specific access control.
2. Program Manager
    1. Purpose: Configures and maintains insurance programs, including program transition stages and defining underwriting factors.
    2. Key Responsibilities: 
        - Defines and manages **program factors**, including underwriting criteria, pricing models, and eligibility requirements.
        - Manages **program stages**, transitioning programs from **development** to **staging** and ultimately **production**.
        - Oversees **Third-Party Administrators (TPAs)**, ensuring correct integration with external partners.
        - Ensures compliance with carrier-specific underwriting guidelines and regulations.
    3. Example Interactions:
        - Works with RateManager to ensure accurate program-based rating.
        - Syncs with QuoteManager and PolicyManager to enforce underwriting rules.
        - Integrates with TpaManager for external program-related services.
3. LossManager
    1. Purpose: Manages the entire claims lifecycle, from FNOL to settlement.
    2. Key Responsibilities:
        - Tracks claim submissions, investigations, and resolutions.
        - Integrates with **FnolManager** for initial loss reporting.
        - Interfaces with **DocumentManager** to store claim-related files and evidence.
        - Calculates claim payouts and manages settlement processes.
    3. Example Interactions:
        - Works with **FnolManager** to initiate claims.
        - Uses **CommunicationManager** for sending claim status updates to insureds and agents.
        - Pulls policy details from **PolicyManager** to validate claims.
4. PolicyManager
    1. Purpose: Manages policy creation, endorsements, renewals, and cancellations.
    2. Key Responsibilities:
        - Tracks all active, expired, and pending policies.
        - Handles policy lifecycle events like renewals, mid-term adjustments, and cancellations.
        - Generates policy documents and stores them in **DocumentManager**.
        - Ensures compliance with underwriting rules from **ProgramManager**.
    3. Example Interactions:
        - Feeds policy data to **LossManager**, **GenerateQuote**, and **PolicyBinder**.
        - Works with **ProducerPortal** to allow agents to manage policies.
        - Syncs with **AccountingManager** for premium calculations and billing.
5. CancellationManager
    1. Purpose: The **CancellationManager** handles all policy cancellations, ensuring compliance with regulatory requirements, financial reconciliation, and proper communication with stakeholders.
    2. Key Responsibilities: 
        - **Process Policy Cancellations**
            - Supports voluntary, involuntary, and underwriting-driven cancellations.
            - Handles full and partial cancellations based on policy terms.
        - **Enforce Cancellation Rules**
            - Validates cancellation eligibility according to state regulations and policy conditions.
            - Identifies required notice periods for different cancellation reasons.
        - **Financial Adjustments & Refunds**
            - Calculates return premiums based on policy term and earned premium formulas.
            - Works with **AccountingManager** to process refunds or outstanding balances.
        - **Notification & Compliance**
            - Generates and sends cancellation notices to policyholders and regulatory bodies.
            - Logs cancellation events for audit tracking and dispute resolution.
    3. Example Interactions: 
        - **PolicyManager**: Updates policy status when a cancellation request is processed.
        - **RateManager**: Adjusts earned premium calculations based on cancellation date.
        - **AccountingManager**: Handles refund issuance or outstanding payment reconciliation.
        - **CommunicationManager**: Sends notifications to policyholders, agents, and regulatory entities.
6. EndorsementManager
    1. Purpose: Handles all endorsement processes, including mid-term policy modifications.
    2. Key Responsibilities:
        - Processes policy changes, including coverage modifications and limit adjustments.
        - Ensures endorsement approval based on underwriting rules.
        - Generates revised policy documents and updates premium calculations.
    3. Example Interactions:
        - Works with **PolicyManager** to apply changes to policies.
        - Syncs with **GenerateRate** to recalculate premiums for endorsements.
        - Integrates with **AccountingManager** for additional premium or refund processing.
7. RenewalManager
    1. Purpose: Manages the policy renewal process.
    2. Key Responsibilities:
        - Identifies policies due for renewal and applies renewal rules.
        - Notifies policyholders and agents of upcoming renewals.
        - Generates new policy terms and premium calculations.
    3. Example Interactions:
        - Works with **PolicyManager** to renew policies automatically.
        - Syncs with **RateManager** to calculate renewal premiums.
        - Uses **CommunicationManager** to notify insureds and producers of renewal status.
8. AccountingManager
    1. Purpose: Manages premium payments, invoicing, commissions, and financial reconciliation.
    2. Key Responsibilities:
        - Tracks premium collections, refunds, and commissions for agents.
        - Integrates with third-party payment gateways.
        - Generates invoices and statements for policyholders and producers.
        - Syncs financial data with **PolicyManager** and **QuoteManager**.
    3. Example Interactions:
        - Works with **SuspenseManager** to handle overdue payments and pending transactions.
        - Sends payment confirmations via **CommunicationManager**.
9. QuoteManager
    1. Purpose: Provides real-time insurance premium estimates based on risk factors.
    2. Key Responsibilities:
        - Uses rating algorithms from **RateManager** to calculate policy costs.
        - Gathers applicant information and performs eligibility checks.
        - Generates bindable quotes that can be converted into policies.
    3. Example Interactions:
        - Connects with **PolicyManager** to store approved quotes.
        - Pulls rating rules from **ProgramManager**.
        - Sends quotes via **CommunicationManager**.
10. RateManager
    1. Purpose: Calculates insurance premiums based on risk factors and carrier rules.
    2. Key Responsibilities:
        - Uses actuarial models and risk analysis for rate determination.
        - Applies carrier-specific rating guidelines from **ProgramManager.**
        - Generates final pricing for **QuoteManager**.
    3. Example Interactions:
        - Integrated into **QuoteManager**, **RenewalManager**, and **EndorsementManager** for premium calculations.
11. PolicyBinder
    1. Purpose: Converts approved quotes into active insurance policies.
    2. Key Responsibilities:
        - Ensures underwriting rules are met before policy issuance.
        - Handles initial premium collection and payment verification.
        - Generates policy documents and stores them in **DocumentManager**.
    3. Example Interactions:
        - Works with **QuoteManager** to finalize policy terms.
        - Sends notifications via **CommunicationManager**.
        - Triggers policy creation in **PolicyManager**.
12. ProducerPortal
    1. Purpose: Interface for insurance agents and brokers to manage policies and clients.
    2. Key Responsibilities:
        - Allows producers to create quotes, bind policies, and track commissions.
        - Provides access to reports and analytics on sales and renewals.
        - Integrates with **AccountingManager** for commission tracking.
    3. Example Interactions:
        - Pulls data from **QuoteManager**, **PolicyBinder**, and **PolicyManager**.
        - Sends communications via **CommunicationManager**.
13. ProducerManager
    1. Purpose: Manages producer information, including agent/broker details and default commission structures.
    2. Key Responsibilities:
        - Stores and manages producer profiles, licenses, and compliance data.
        - Maintains commission structures and default commission settings.
        - Tracks producer performance, production volume, and contractual agreements.
    3. Example Interactions:
        - Works with **AccountingManager** to calculate and process commissions.
        - Integrates with **ProducerPortal** to provide producer data.
        - Pulls policy production data from **PolicyManager** for agent performance tracking.
14. TpaManager
    1. Purpose: Manages third-party API integrations and configuration.
    2. Key Responsibilities:
        - Configures external API connections for payment processing, claims services, and underwriting.
        - Ensures data exchange compliance with external partners.
        - Monitors API performance and error handling.
    3. Example Interactions:
        - Works with **AccountingManager** for third-party payment integrations.
        - Syncs with **LossManager** for external claims services.
        - Integrates with **RateManager** for third-party underwriting rules.
15. ReportManager
    1. Purpose: Responsible for all system report generation.
    2. Key Responsibilities:
        - Generates operational, financial, and compliance reports.
        - Aggregates data from **PolicyManager**, **AccountingManager**, **LossManager**, and **QuoteManager**.
        - Supports scheduled and on-demand report execution.
    3. Example Interactions:
        - Pulls data from **AccountingManager** for financial reports.
        - Works with **PolicyManager** for policy analytics.
16. SystemLogger
    1. Purpose: Manages all system logging and observability.
    2. Key Responsibilities:
        - Collects logs from all microservices for auditing and debugging.
        - Integrates with monitoring tools for system health tracking.
        - Stores logs securely with configurable retention policies.
    3. Example Interactions:
        - Works with **AuthenticationManager** for login and access tracking.
        - Monitors **LossManager** and **FnolManager** for claims processing logs.
        - Logs all communication transactions from **CommunicationManager.**
17. InsuredPortal
    1. Purpose: Interface for policyholders to manage their insurance policies and claims.
    2. Key Responsibilities:
        - Enables insureds to view policy details, make payments, and file claims.
        - Provides access to policy documents and renewal options.
        - Allows direct communication with customer support.
    3. Example Interactions:
        - Connects with **PolicyManager** for policy details.
        - Works with **LossManager** for claim tracking.
        - Uses **AccountingManager** for premium payments.
18. FnolManager
    1. Purpose: Manages the First Notice of Loss (FNOL) process for claims initiation.
    2. Key Responsibilities:
        - Captures incident details and policyholder reports.
        - Assigns claim adjusters and routes cases to **LossManager**.
        - Collects supporting documents and images via **DocumentManager**.
    3. Example Interactions:
        - Works with **LossManager** for claim processing.
        - Uses **CommunicationManager** for claim status updates.
19. CommunicationManager
    1. Purpose: Handles all outbound communications via multiple channels.
    2. Key Responsibilities:
        - Manages email (SendGrid), SMS (Twilio), and batch mail processing.
        - Automates notifications for policy renewals, claim updates, and billing.
    3. Example Interactions:
        - Works with **QuoteManager**, **LossManager**, and **AccountingManager**.
20. DocumentManager
    1. Purpose: Stores, retrieves, and processes documents and electronic signatures.
    2. Key Responsibilities:
        - Stores policy documents, claim reports, and customer correspondence.
        - Handles electronic signing of agreements and forms.
        - Provides versioning and audit logging for compliance.
    3. Example Interactions:
        - Works with **PolicyManager** for policy documents.
        - Integrates with **FnolManager** for claims documentation.
21. SuspenseManager
    1. Purpose: System-wide task assignment and workflow management.
    2. Key Responsibilities:
        - Tracks pending payments, policy approvals, and claim verifications.
        - Assigns tasks to underwriters, adjusters, and support teams.
        - Provides reminders and escalation workflows.
    3. Example Interactions:
        - Works with **AccountingManager** for overdue payments.
        - Assigns FNOL tasks to adjusters in **LossManager.**

### 39.0 Development Tools Requirements

The development environment must be standardized across all team members to ensure consistency, efficiency, and maintainability. The following tools are required for all developers working on the system. All dependencies should be versioned properly using lock files (composer.lock, package-lock.json) to maintain compatibility across teams.

Core Development Tools

1. Integrated Development Environment (IDE) 
    - Tool: PhpStorm / VSCode
    - Version: Latest
    - Purpose:
        - Provides a structured development environment.
        - Supports code navigation, debugging, and refactoring.
        - Enhances productivity with built-in PHP, JavaScript, and Docker support.
2. Version Control
    - Tool: Git
    - Version: 2.40+
    - Purpose:
        - Facilitates collaborative development and code versioning.
        - Ensures proper branching strategies and commit history tracking.
        - Supports GitLab integration for CI/CD pipelines.
3. Containerization & Orchestration
    1. Container Runtime
        - Tool: Docker
        - Version: 24.0+
        - Purpose:
            - Enables isolated application environments for local development.
            - Provides a consistent runtime for microservices.
    2. Docker Compose
        - Tool: Docker Compose
        - Version: Latest
        - Purpose:
            - Manages multi-container environments for local development.
            - Defines and runs dependent services such as databases, caching layers, and queues.
    3. Docker Desktop
        - Tool: Docker Desktop
        - Version: Latest
        - Purpose:
            - Provides a UI and management layer for local Docker instances.
            - Simplifies container lifecycle management for developers.
4. Dependency Management
    - Tool: Composer / npm
    - Version: Latest
    - Purpose:
        - Handles PHP dependencies (Composer) and JavaScript packages (npm).
        - Ensures package version consistency across all environments.
        - Supports integration with Laravel, Vue.js, and other frameworks.

### 40.0 Database Seeding Requirements

Laravel’s database seeders must be utilized to efficiently populate databases with initial data and test datasets for development, testing, and staging environments. This ensures consistent application behavior across different environments while allowing automated data provisioning.

1. Standardized Seeding Implementation
    - All seeders must extend Laravel’s Seeder class and be located in the database/seeders directory.
    - Seeders must be structured to allow modular execution, enabling the execution of individual seeders without requiring a full database refresh.
    - Factories should be used whenever possible to generate large datasets efficiently.
2. Types of Seeders
    1. System Seeders (Initial Data for Core Setup)
        - These seeders must contain essential system data that is required for the application to function.
        - Examples:
            - User Roles & Permissions – Populates roles and permissions tables.
            - System Configurations – Inserts default configuration settings.
            - Lookup Tables – Populates dropdowns, statuses, and static reference data.
    2. Testing Seeders (Development & QA Datasets)
        - Testing seeders should populate the database with mock records for functional and UI testing.
        - Examples:
            - Fake Users – Populates users with randomized names and emails.
            - Sample Policies & Claims – Generates realistic insurance policy and claim records for testing.
            - Transaction History – Populates financial transactions to simulate real-world usage.
    3. Demo Data Seeders (For Stading Environments)
        - These seeders must insert non-sensitive but meaningful data for demonstration purposes.
        - Used in client-facing demo environments to showcase application functionality.
        - Examples:
            - Sample insured and producer accounts.
            - Pre-filled quotes, policies, and claims for walkthroughs.
3. Seeder Execution Requirements
    1. Running Seeders
        - The system must allow seeders to be executed individually or in groups as needed.
        - Laravel’s DatabaseSeeder class must orchestrate execution when seeding the entire system.
    2. Reset & Reseed Workflow
        - Developers and testers must be able to wipe and reseed the database when needed for fresh test runs.
        - The reseeding process should drop all tables, recreate the schema, and repopulate with seed data in a single command.
4. Best Practices for Seeders
    1. Ensure Idempotency
        - Seeders should be re-runnable without causing duplicate data.
        - Existing records should be checked before inserting new data to prevent duplication.
    2. Use Factories for Bulk Data Generation
        - Factories must be used to generate large datasets efficiently for testing and demo environments.
        - Factories allow for scalable data generation with minimal code repetition.
    3. Environment-Specific Seeding
        - Seeders must be able to dynamically adjust based on environment settings to prevent accidental data insertion in production.
        - Testing data must never be seeded into production environments.
5. Security & Performance Considerations
    - Real customer data must never be used in testing or demo seeders.
    - Large seed operations should be handled in batches to prevent memory exhaustion.
    - Production deployments must not automatically execute seeders, except for system seeders necessary for setup.

### 41.0 Table Schema Requirements

To systematically outline and standardize table schema requirements, we must use a structured and hierarchical approach.

- Understand Table Relationships – Primary keys, foreign keys, and associations.
- Maintain Consistency – Follow a predefined naming convention.
- Ensure Completeness – Include required constraints, descriptions, and references.
- Follow Best Practices – Adhere to database standards (e.g., MySQL/MariaDB/PostgreSQL best practices).
1. Structured Table Schema Explanation 
    1. General Table Structure
        1. Each table follows a consistent format, containing:
            - Primary Key (id) – Uniquely identifies each record.
            - Foreign Keys – References related entities.
            - Status Tracking – Uses a status_id column for record state management.
            - Audit Fields – Tracks user interactions (created_by, updated_by) and timestamps (created_at, updated_at).
            - Description Column (if applicable) – Stores additional details about the record.
    2. Naming Conventions
        - Tables: Use singular nouns (e.g., user, policy, producer).
        - Join Tables (Mapping Tables): Prefixed with map_ and use singular nouns (e.g., map_user_group, map_producer_signature).
        - Status Tables: Single status table and status_type table. status.id referred to in each map_ table.
        - Reference Tables: Use _type suffix (e.g., user_type, producer_type).
        - Timestamps: Always include created_at, updated_at for record lifecycle tracking.
    3. Standardized Table Types
        1. Each table falls into one of the following categories:
            - Core Entity Tables
                - Store primary records in the system.
                - Example: user, policy, producer.
            - Reference Tables
                - Define categories for entity classification.
                - Example: user_type, producer_type, document_type.
            - Mapping Tables (map_*)
                - Define many-to-many relationships.
                - Example: map_user_group, map_producer_user.
            - Status & Lifecycle Tracking
                - Track entity state (e.g., active, inactive).
                - Example: status, policy_status.
            - Transaction & Logs
                - Track financial and system transactions.
                - Example: transaction, log_action.
2. Conclusion
    - Tables use singular nouns.
    - Join tables use map_ prefix.
    - Each table includes a primary key (id), status tracking (status_id), and audit fields (created_by, updated_by, created_at, updated_at).
    - Use BIGINT for IDs, VARCHAR(255) for names, and TEXT for descriptions.
    - Ensure foreign keys reference appropriate parent tables.
    - Ensure timestamps track data changes.

### 42.0 System Configuration Options

1. Purpose
    1. The System Configuration Options framework is designed to provide a centralized and flexible way to manage system-wide settings. It enables dynamic configuration of key system behaviors, reducing the need for hardcoded values and allowing for easy updates without requiring system redeployment.
2. Key Responsibilities
    1. Centralized Configuration Management
        - A single source of truth for all configurable system settings stored in the configuration table.
        - Ensures consistency across all system components and services.
        - Allows for easy retrieval and updates of configuration values via API and database queries.
        - Relevant Fields:
            - configuration.id – Unique identifier for the configuration.
            - configuration.name – Name of the configuration setting.
            - configuration.value – The actual value assigned to the configuration.
            - configuration.description – Additional details explaining the configuration's purpose.
    2. Configurable Parameters
        - Supports different types of configurations, such as numeric values (e.g., range limits), boolean toggles (e.g., enable/disable features), and text-based options (e.g., predefined categories).
        - Allows for default values while enabling granular overrides at program or policy levels where needed.
        - Relevant Fields:
            - configuration_type.id – Unique identifier for the configuration type.
            - configuration_type.name – Defines the category of the configuration type (e.g., "Minimum Range Value," "Maximum Range Value," "Boolean Toggle").
            - configuration.value – The stored value, adaptable to different formats based on the configuration_type.
    3. Configuration Grouping
        - Organizes related configurations into logical groups for easier management using the configuration_group table.
        - Allows quick retrieval of all settings related to a particular function (e.g., rating rules, policy settings).
        - Relevant Fields:
            - configuration_group.id – Unique identifier for a group of configurations.
            - configuration_group.name – Name of the configuration group (e.g., "Rating Factors").
            - map_program_configuration_group.configuration_id – Maps configurations to specific programs.
            - map_policy_configuration_group.configuration_id – Maps configurations to specific policies.
    4. Tiered Configuration Levels
        - System-Level Settings: Global defaults that apply across all programs and policies.
        - Program-Level Overrides: Custom configurations tailored to specific insurance programs (map_program_configuration_group).
        - Policy-Level Overrides: Granular settings that apply to individual policies when necessary (map_policy_configuration_group).
        - Relevant Fields:
            - map_program_configuration_group.program_id – Links a configuration to a program.
            - map_policy_configuration_group.policy_id – Links a configuration to a policy.
            - configuration.value – Holds the actual setting value.
    5. Status-Based Control and Auditing
        - Supports active/inactive states for configurations to ensure controlled rollout and testing using configuration.status_id.
        - Tracks who created, modified, and last updated each configuration for auditing and compliance.
        - Relevant Fields:
            - configuration.status_id – References the status of the configuration (e.g., active, inactive).
            - configuration.created_by – Stores the user ID of the creator.
            - configuration.updated_by – Tracks the last user who updated the configuration.
            - configuration.created_at / configuration.updated_at – Timestamp fields for auditing.
3. Functional Capabilities
    1. Configuration Definition and Management
        - Admin users should be able to create, update, and deactivate configurations through a user interface.
        - Configurations should support validation rules to prevent invalid entries.
        - Relevant Fields:
            - configuration.name – Ensures unique, identifiable settings.
            - configuration.value – Must adhere to validation rules defined in configuration_type.
    2. Dynamic Configuration Retrieval 
        - System components and services should be able to request configuration values dynamically via an API.
        - Responses should provide the most specific applicable setting (system, program, or policy level).
        - Relevant Fields:
            - map_program_configuration_group.configuration_id – Defines program-specific settings.
            - map_policy_configuration_group.configuration_id – Defines policy-specific overrides.
    3. Override and Hierarchical Logic
        - Policies inherit program-level configurations unless a policy-specific override is defined.
        - Programs inherit system-level defaults unless a custom setting is applied.
        - The system must correctly prioritize and apply the most relevant configuration.
        - Relevant Fields:
            - configuration.value – The stored configuration setting.
            - map_policy_configuration_group.policy_id – Overrides at the policy level.
            - map_program_configuration_group.program_id – Overrides at the program level.
    4. System and Access Control 
        - Only authorized users should be able to modify configurations.
        - Audit logs should record all changes for compliance tracking.
        - Relevant Fields:
            - configuration.created_by / configuration.updated_by – Tracks changes.
            - configuration.status_id – Controls active/inactive state.
    5. Performance and Scalability 
        - Configuration retrieval should be optimized for high-performance lookups.
        - The system should support caching to minimize database queries.
        - Relevant Fields:
            - configuration.value – Stored efficiently for quick lookup.
            - Indexing configuration.name, configuration_group.name, and status_id for performance optimization.

# **#9: REFERENCES & ATTACHMENTS**

1. **Development-Ready Designs:**
    1. Development-ready, annotated UI designs can be found [HERE](https://www.figma.com/design/wZy88tHbSjEicvzgzVqZH2/Insure-Pilot---MVP-Designs?node-id=89-29705&t=x8YeKJksv4OPpSVn-1) to guide front-end and back-end implementation efforts. The password to view the file is `insurepilot2025` . These designs include specifications for component states, interactions, and data mappings, ensuring that developers have a clear visual reference for building the Document Management feature.
```