# Insure Pilot Documents View User Guide

## Table of Contents

- [Introduction](#introduction)
  - [Purpose and Benefits](#purpose-and-benefits)
  - [Accessing the Documents View](#accessing-the-documents-view)
  - [User Interface Overview](#user-interface-overview)
- [Getting Started](#getting-started)
  - [System Requirements](#system-requirements)
  - [Accessing Documents](#accessing-documents)
  - [Interface Layout](#interface-layout)
  - [Navigation Controls](#navigation-controls)
- [Viewing Documents](#viewing-documents)
  - [Opening Documents](#opening-documents)
  - [PDF Navigation](#pdf-navigation)
  - [Zoom and View Controls](#zoom-and-view-controls)
  - [Document Information](#document-information)
- [Managing Metadata](#managing-metadata)
  - [Understanding Metadata Fields](#understanding-metadata-fields)
  - [Editing Metadata](#editing-metadata)
  - [Field Dependencies](#field-dependencies)
  - [Type-ahead Filtering](#type-ahead-filtering)
  - [Saving Changes](#saving-changes)
- [Document Processing](#document-processing)
  - [Marking Documents as Processed](#marking-documents-as-processed)
  - [Unprocessing Documents](#unprocessing-documents)
  - [Trashing Documents](#trashing-documents)
  - [Document Status Indicators](#document-status-indicators)
- [Document History](#document-history)
  - [Accessing Document History](#accessing-document-history)
  - [Understanding History Entries](#understanding-history-entries)
  - [Returning to Metadata View](#returning-to-metadata-view)
- [Contextual Navigation](#contextual-navigation)
  - [Using the Ellipsis Menu](#using-the-ellipsis-menu)
  - [Navigating to Related Records](#navigating-to-related-records)
  - [Returning to Documents View](#returning-to-documents-view)
- [Keyboard Shortcuts](#keyboard-shortcuts)
  - [Navigation Shortcuts](#navigation-shortcuts)
  - [Action Shortcuts](#action-shortcuts)
  - [Accessibility Features](#accessibility-features)
- [Troubleshooting](#troubleshooting)
  - [Document Loading Issues](#document-loading-issues)
  - [Metadata Saving Problems](#metadata-saving-problems)
  - [Permission Errors](#permission-errors)
  - [Browser Compatibility](#browser-compatibility)
- [Glossary](#glossary)
  - [Document Terms](#document-terms)
  - [Metadata Terms](#metadata-terms)
  - [Process Status Terms](#process-status-terms)

## Introduction

### Purpose and Benefits

The Documents View in Insure Pilot provides a dedicated, full-screen environment for reviewing, processing, and managing insurance-related documents. This feature is designed to improve your workflow efficiency when handling documents by:

- Centralizing document viewing and metadata management in one interface
- Streamlining the document review process
- Providing clear tracking of document status and history
- Enabling quick navigation to related records
- Reducing the time needed to process documents

The Documents View helps you focus on the task at hand without switching between different screens or applications.

### Accessing the Documents View

The Documents View can be accessed from several locations within Insure Pilot:

- From the Documents tab in any policy, claim, or producer record
- From the main Documents menu in the navigation bar
- From document search results
- From your Recent Documents list on the dashboard

Click on any document thumbnail or link to open it in the Documents View.

### User Interface Overview

![Overview of the Documents View interface showing the dual-panel layout](documents_view_overview.png)

The Documents View features a dual-panel layout:

- **Left Panel**: Displays the document using Adobe Acrobat PDF viewer
- **Right Panel**: Shows metadata fields and document actions

This layout allows you to view document content while simultaneously managing metadata, creating an efficient workflow for document processing.

## Getting Started

### System Requirements

To use the Documents View feature effectively, ensure your system meets these requirements:

- **Browser**: Chrome 90+, Firefox 88+, Edge 90+, or Safari 14+
- **Screen Resolution**: Minimum 1280 x 800 pixels (larger recommended)
- **PDF Viewer**: Adobe Acrobat Reader plugin should be enabled in your browser
- **Permissions**: You need appropriate document access permissions

### Accessing Documents

To access a document in the Documents View:

1. Navigate to any document list in Insure Pilot
2. Click on a document thumbnail or name
3. The document will open in the full-screen Documents View

You can also access documents from:

- Recent Documents list
- Search results
- Related documents in policy, claim, or producer records

### Interface Layout

The Documents View is divided into two main panels:

**Left Panel (Document Display)**
- PDF document viewer
- Page navigation controls
- Document filename
- Zoom and view controls

**Right Panel (Metadata)**
- Document metadata fields
- Processing actions (Mark as Processed, Trash)
- Document History link
- Contextual navigation menu (ellipsis)

### Navigation Controls

The Documents View provides several navigation controls:

- **Close Button (X)**: Located in the top-right corner, closes the Documents View
- **Page Controls**: Navigate between pages in multi-page documents
- **Zoom Controls**: Adjust document zoom level
- **Back Button**: Return to the previous view when in Document History
- **Ellipsis Menu**: Access contextual navigation options

## Viewing Documents

### Opening Documents

When you select a document from anywhere in Insure Pilot, it opens in the Documents View automatically. The document will display in the left panel, and its metadata will appear in the right panel.

If the document is loading, you'll see a loading indicator. Large documents may take longer to load.

### PDF Navigation

For multi-page documents, use the navigation controls at the bottom of the left panel:

- **Previous Page Arrow (<)**: Move to the previous page
- **Next Page Arrow (>)**: Move to the next page
- **Page Indicator**: Shows current page and total pages (e.g., "Page 1 of 3")

You can also scroll through the document using your mouse wheel or trackpad.

### Zoom and View Controls

The PDF viewer provides several viewing options:

- **Zoom In/Out**: Magnify or reduce the document view
- **Fit to Width**: Adjust the document to fit the width of the panel
- **Fit to Page**: Show the entire page in the panel
- **Rotate**: Rotate the document if needed

These controls appear at the bottom of the PDF viewer when you hover over the document.

### Document Information

The document filename appears above the PDF viewer. Additional document information is displayed in the metadata panel, including:

- Date received
- Document type
- Associated policies, claims, or producers
- Processing status

## Managing Metadata

### Understanding Metadata Fields

![Close-up of the metadata panel showing editable fields](metadata_panel.png)

The metadata panel displays several fields that help categorize and organize the document:

- **Policy Number**: The associated insurance policy
- **Loss Sequence**: The specific claim or loss event
- **Claimant**: The individual making the claim
- **Document Description**: The type or purpose of the document
- **Assigned To**: The person or department responsible for the document
- **Producer Number**: The insurance agent or broker

Not all fields may be relevant for every document, and some fields may be required depending on your organization's policies.

### Editing Metadata

To edit document metadata:

1. Click on any field in the metadata panel
2. For dropdown fields, select from the available options
3. For text fields, type the desired information
4. The system automatically saves changes as you make them
5. A "Saving..." indicator appears briefly, followed by "Saved" when complete

If a field is read-only, you cannot edit it. This may be due to permission restrictions or because the document has been marked as processed.

### Field Dependencies

Some metadata fields are dependent on others:

- **Loss Sequence**: Available options depend on the selected Policy Number
- **Claimant**: Available options depend on the selected Loss Sequence
- **Policy Number**: Available options may be filtered based on Producer Number

When you change a parent field, dependent fields will update automatically to show relevant options.

### Type-ahead Filtering

![Example of type-ahead filtering in dropdown fields](dropdown_filtering.png)

All dropdown fields support type-ahead filtering to help you quickly find options:

1. Click on a dropdown field
2. Begin typing to filter the list
3. The dropdown will show only options matching your text
4. Select the desired option from the filtered list

This feature is especially helpful for fields with many options, like Policy Number or Producer Number.

### Saving Changes

The Documents View automatically saves your changes as you make them:

1. When you change a field value, a "Saving..." indicator appears
2. Once saved, the indicator changes to "Saved"
3. If there's an error, an error message will display with details

There is no need to manually save your changes or click a save button.

## Document Processing

### Marking Documents as Processed

After reviewing a document and updating its metadata, you can mark it as processed:

1. Review the document content and ensure all metadata is correct
2. Click the "Mark as Processed" button at the bottom of the metadata panel
3. The metadata fields will become read-only
4. The button will change to "Processed"

Marking a document as processed indicates that you have reviewed it and completed any necessary actions.

### Unprocessing Documents

If you need to edit a document that has been marked as processed:

1. Click the "Processed" button
2. The button will change back to "Mark as Processed"
3. The metadata fields will become editable again

Note: Depending on your permissions, you may not be able to unprocess documents that others have processed.

### Trashing Documents

If a document is no longer needed or was uploaded in error:

1. Click the trash icon in the metadata panel
2. A confirmation dialog will appear
3. Click "Trash Document" to confirm
4. The document will be moved to the Recently Deleted folder

Trashed documents can be recovered within 90 days. After that period, they will be permanently deleted from the system.

### Document Status Indicators

![A document in processed state with read-only metadata](processed_document.png)

Documents can have different status indicators:

- **No indicator**: Document is unprocessed and editable
- **Processed**: Document has been marked as processed (read-only)
- **Trashed**: Document has been moved to Recently Deleted
- **Assigned**: Document has been assigned to a specific user or department

The current status is reflected in both the metadata panel and in document list views throughout Insure Pilot.

## Document History

### Accessing Document History

To view the history of actions performed on a document:

1. Click the "Document History" link at the bottom of the metadata panel
2. The metadata panel will switch to display the document history
3. The most recent actions appear at the top of the list

The history view shows who has viewed, edited, or processed the document, and when these actions occurred.

### Understanding History Entries

![The document history panel showing audit trail entries](document_history.png)

The document history displays:

- **Last Edited**: The most recent edit timestamp and user
- **Action Entries**: Chronological list of all actions taken on the document

Each history entry includes:
- Date and time of the action
- Name of the user who performed the action
- Type of action (viewed, edited, processed, etc.)
- Details of changes made (for edit actions)

This audit trail helps track who has interacted with the document and what changes they made.

### Returning to Metadata View

To return to the metadata panel from the history view:

1. Click the back arrow in the top-right corner of the history panel
2. The display will switch back to show the document metadata

## Contextual Navigation

### Using the Ellipsis Menu

The Documents View provides contextual navigation options through the ellipsis menu:

1. Click the ellipsis icon (three dots) in the metadata panel
2. A menu will appear with navigation options relevant to the current document
3. Available options depend on the document's metadata

### Navigating to Related Records

From the ellipsis menu, you can navigate directly to related records:

- **Go to Policy**: Opens the associated policy record
- **Go to Producer View**: Opens the associated producer record
- **Go to Claimant View**: Opens the associated claimant record

These options appear based on the metadata associated with the document. For example, "Go to Policy" only appears if the document has a Policy Number.

### Returning to Documents View

After navigating to a related record, you can return to the Documents View:

1. Locate the document in the related record's Documents tab
2. Click on the document to reopen it in the Documents View

Alternatively, you can use your browser's back button to return to the previous view.

## Keyboard Shortcuts

### Navigation Shortcuts

The Documents View supports several keyboard shortcuts for navigation:

- **Esc**: Close the Documents View
- **←/→**: Navigate to previous/next page in the document
- **Home/End**: Go to first/last page of the document
- **Tab**: Move between interactive elements
- **Shift+Tab**: Move between interactive elements in reverse order

### Action Shortcuts

Action shortcuts help you perform common tasks more efficiently:

- **Ctrl+S**: Save current changes (though changes save automatically)
- **Ctrl+P**: Mark document as processed or unprocess it
- **Ctrl+H**: View document history
- **Ctrl+E**: Open ellipsis menu

### Accessibility Features

The Documents View includes several accessibility features:

- **Screen Reader Support**: All elements have appropriate ARIA labels
- **Keyboard Navigation**: All functions can be accessed without a mouse
- **Focus Indicators**: Visual indicators show keyboard focus location
- **High Contrast Text**: Text meets WCAG AA contrast requirements

## Troubleshooting

### Document Loading Issues

If a document fails to load:

1. **Check your internet connection**: Ensure you have a stable connection
2. **Refresh the page**: Use your browser's refresh button or press F5
3. **Check PDF viewer**: Ensure Adobe Acrobat Reader is enabled in your browser
4. **Try a different browser**: Some browser extensions may interfere with the PDF viewer
5. **Contact support**: If the problem persists, contact your system administrator

### Metadata Saving Problems

If you encounter issues saving metadata changes:

1. **Check for error messages**: Read any error messages for specific instructions
2. **Verify required fields**: Ensure all required fields are completed
3. **Check field dependencies**: Ensure dependent fields have valid selections
4. **Refresh and try again**: Refresh the page and attempt to make changes again
5. **Check permissions**: Ensure you have permission to edit the document

### Permission Errors

If you receive permission errors:

1. **Verify your role**: Ensure your user role includes document editing permissions
2. **Check document status**: Processed documents cannot be edited without unprocessing them first
3. **Check document ownership**: Some documents may be restricted to specific departments or users
4. **Contact your administrator**: Request appropriate permissions if needed

### Browser Compatibility

For optimal performance:

1. **Use a supported browser**: Chrome, Firefox, Edge, or Safari in their latest versions
2. **Keep your browser updated**: Outdated browsers may have compatibility issues
3. **Clear browser cache**: Sometimes clearing your cache resolves display issues
4. **Disable conflicting extensions**: Some browser extensions may interfere with functionality

## Glossary

### Document Terms

- **PDF**: Portable Document Format, the standard format for documents in Insure Pilot
- **Lightbox**: The full-screen overlay that displays the Documents View
- **Thumbnail**: Small preview image of a document in document lists
- **Recently Deleted**: Folder containing documents that have been trashed but not permanently deleted

### Metadata Terms

- **Policy Number**: Unique identifier for an insurance policy
- **Loss Sequence**: Numbered identifier for a claim or loss event under a policy
- **Claimant**: Person or entity making a claim
- **Document Description**: Classification of the document type or purpose
- **Producer Number**: Identifier for an insurance agent or broker
- **Assigned To**: Person or department responsible for processing the document

### Process Status Terms

- **Unprocessed**: Default status for documents that have not been reviewed or processed
- **Processed**: Status indicating a document has been reviewed and its metadata finalized
- **Trashed**: Status for documents moved to the Recently Deleted folder
- **Recovered**: Status for documents that were trashed and then restored

---

For additional assistance, please contact your system administrator or refer to the Insure Pilot help desk resources.