## Introduction

This document provides comprehensive documentation for the Documents View API, which enables frontend applications to interact with the document management system. The API follows RESTful principles and uses JSON for data exchange.

### Base URL

All API endpoints are relative to the base URL: `/api`

### API Versioning

The current API version is v1, which is implicit in all endpoints. Future versions will be explicitly versioned as `/api/v2/...`

## Authentication

The Documents View API uses Laravel Sanctum for authentication. All API requests must include a valid authentication token.

### Token Authentication

Include the token in the Authorization header: `Authorization: Bearer {token}`

### Token Expiration

Tokens expire after 24 hours. Applications should handle token refresh appropriately.

### CSRF Protection

For SPA applications, CSRF protection is enabled. Include the XSRF-TOKEN cookie value in the X-XSRF-TOKEN header.

## Rate Limiting

API endpoints are subject to rate limiting to prevent abuse. The current limits are:

### Document Retrieval

100 requests per minute with burst allowance of 150 requests

### Metadata Updates

60 requests per minute with burst allowance of 100 requests

### Document Processing

30 requests per minute with burst allowance of 50 requests

### Bulk Operations

10 requests per minute with burst allowance of 15 requests

## Error Handling

The API uses standard HTTP status codes and returns error details in a consistent JSON format.

### Error Response Format

```json
{
  "error": {
    "code": "error_code",
    "message": "Human-readable error message",
    "details": {}
  }
}
```

### Common Error Codes

- 400: Bad Request - Invalid input parameters
- 401: Unauthorized - Authentication required
- 403: Forbidden - Insufficient permissions
- 404: Not Found - Resource not found
- 422: Unprocessable Entity - Validation errors
- 429: Too Many Requests - Rate limit exceeded
- 500: Internal Server Error - Server-side error

### Validation Errors

Validation errors return a 422 status code with details about each invalid field:
```json
{
  "error": {
    "code": "validation_error",
    "message": "The given data was invalid.",
    "details": {
      "policy_number": ["The policy number field is required."]
    }
  }
}
```

## Document Endpoints

Endpoints for managing documents and their metadata.

### GET /api/documents

Retrieves a paginated list of documents with optional filtering.

**Query Parameters:**
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 20, max: 100)
- `status`: Filter by status (e.g., 'processed', 'unprocessed', 'trashed')
- `search`: Search term for document name or description
- `sort`: Field to sort by (default: 'created_at')
- `direction`: Sort direction ('asc' or 'desc', default: 'desc')

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Policy_Renewal_Notice.pdf",
      "description": "Policy renewal document",
      "status": "unprocessed",
      "created_at": "2023-05-10T09:15:00Z",
      "updated_at": "2023-05-12T10:45:00Z",
      "metadata": {
        "policy_number": "PLCY-12345",
        "loss_sequence": "1 - Vehicle Accident (03/15/2023)",
        "claimant": "1 - John Smith",
        "document_description": "Policy Renewal Notice",
        "assigned_to": "Claims Department",
        "producer_number": "AG-789456"
      }
    }
  ],
  "links": {
    "first": "http://example.com/api/documents?page=1",
    "last": "http://example.com/api/documents?page=5",
    "prev": null,
    "next": "http://example.com/api/documents?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "path": "http://example.com/api/documents",
    "per_page": 20,
    "to": 20,
    "total": 100
  }
}
```

### GET /api/documents/{id}

Retrieves a specific document by ID.

**Path Parameters:**
- `id`: Document ID

**Response:**
```json
{
  "data": {
    "id": 1,
    "name": "Policy_Renewal_Notice.pdf",
    "description": "Policy renewal document",
    "status": "unprocessed",
    "created_at": "2023-05-10T09:15:00Z",
    "updated_at": "2023-05-12T10:45:00Z",
    "file_url": "http://example.com/api/documents/1/file",
    "metadata": {
      "policy_number": "PLCY-12345",
      "loss_sequence": "1 - Vehicle Accident (03/15/2023)",
      "claimant": "1 - John Smith",
      "document_description": "Policy Renewal Notice",
      "assigned_to": "Claims Department",
      "producer_number": "AG-789456"
    }
  }
}
```

### GET /api/documents/{id}/file

Retrieves the file associated with a document.

**Path Parameters:**
- `id`: Document ID

**Response:**
The document file with appropriate content type headers.

### PUT /api/documents/{id}

Updates a document's metadata.

**Path Parameters:**
- `id`: Document ID

**Request Body:**
```json
{
  "name": "Updated_Policy_Notice.pdf",
  "description": "Updated policy document"
}
```

**Response:**
```json
{
  "data": {
    "id": 1,
    "name": "Updated_Policy_Notice.pdf",
    "description": "Updated policy document",
    "status": "unprocessed",
    "created_at": "2023-05-10T09:15:00Z",
    "updated_at": "2023-05-12T11:30:00Z",
    "file_url": "http://example.com/api/documents/1/file",
    "metadata": {
      "policy_number": "PLCY-12345",
      "loss_sequence": "1 - Vehicle Accident (03/15/2023)",
      "claimant": "1 - John Smith",
      "document_description": "Policy Renewal Notice",
      "assigned_to": "Claims Department",
      "producer_number": "AG-789456"
    }
  }
}
```

### POST /api/documents/{id}/process

Marks a document as processed or unprocessed.

**Path Parameters:**
- `id`: Document ID

**Request Body:**
```json
{
  "process_state": true
}
```
Set `process_state` to `true` to mark as processed, `false` to mark as unprocessed.

**Response:**
```json
{
  "message": "Document marked as processed successfully",
  "status": "processed"
}
```

### POST /api/documents/{id}/trash

Moves a document to the trash.

**Path Parameters:**
- `id`: Document ID

**Response:**
```json
{
  "message": "Document moved to trash successfully"
}
```

## Document History Endpoints

Endpoints for retrieving document history and audit trail information.

### GET /api/documents/{id}/history

Retrieves the history of actions performed on a document.

**Path Parameters:**
- `id`: Document ID

**Query Parameters:**
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 20, max: 100)
- `direction`: Sort direction ('asc' or 'desc', default: 'desc')

**Response:**
```json
{
  "data": [
    {
      "id": 101,
      "action_type": "processed",
      "description": "Marked as processed",
      "created_at": "2023-05-12T10:45:00Z",
      "user": {
        "id": 5,
        "username": "sarah.johnson",
        "name": "Sarah Johnson"
      }
    },
    {
      "id": 100,
      "action_type": "metadata_updated",
      "description": "Changed Document Description from 'Policy Document' to 'Policy Renewal Notice'",
      "created_at": "2023-05-12T10:42:00Z",
      "user": {
        "id": 5,
        "username": "sarah.johnson",
        "name": "Sarah Johnson"
      }
    },
    {
      "id": 99,
      "action_type": "viewed",
      "description": "Document viewed",
      "created_at": "2023-05-12T10:40:00Z",
      "user": {
        "id": 5,
        "username": "sarah.johnson",
        "name": "Sarah Johnson"
      }
    },
    {
      "id": 98,
      "action_type": "created",
      "description": "Document uploaded",
      "created_at": "2023-05-10T09:15:00Z",
      "user": {
        "id": 1,
        "username": "system",
        "name": "System"
      }
    }
  ],
  "links": {
    "first": "http://example.com/api/documents/1/history?page=1",
    "last": "http://example.com/api/documents/1/history?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "path": "http://example.com/api/documents/1/history",
    "per_page": 20,
    "to": 4,
    "total": 4
  }
}
```

### GET /api/documents/{id}/history/last-edited

Retrieves information about the last edit made to a document.

**Path Parameters:**
- `id`: Document ID

**Response:**
```json
{
  "data": {
    "timestamp": "2023-05-12T10:45:00Z",
    "user": {
      "id": 5,
      "username": "sarah.johnson",
      "name": "Sarah Johnson"
    },
    "action": "processed",
    "description": "Marked as processed"
  }
}
```

### GET /api/documents/{id}/history/action-types

Retrieves a list of action types used in a document's history.

**Path Parameters:**
- `id`: Document ID

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "created",
      "description": "Document created"
    },
    {
      "id": 2,
      "name": "viewed",
      "description": "Document viewed"
    },
    {
      "id": 3,
      "name": "metadata_updated",
      "description": "Document metadata updated"
    },
    {
      "id": 4,
      "name": "processed",
      "description": "Document marked as processed"
    },
    {
      "id": 5,
      "name": "unprocessed",
      "description": "Document marked as unprocessed"
    },
    {
      "id": 6,
      "name": "trashed",
      "description": "Document moved to trash"
    }
  ]
}
```

### GET /api/documents/{id}/history/filter

Filters document history by action type.

**Path Parameters:**
- `id`: Document ID

**Query Parameters:**
- `action_type_id`: ID of the action type to filter by
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 20, max: 100)

**Response:**
Same format as the general history endpoint, but filtered to the specified action type.

## Metadata Endpoints

Endpoints for managing document metadata and retrieving dropdown options.

### GET /api/metadata/documents/{documentId}

Retrieves metadata for a specific document.

**Path Parameters:**
- `documentId`: Document ID

**Response:**
```json
{
  "data": {
    "policy_number": "PLCY-12345",
    "policy_id": 42,
    "loss_sequence": "1 - Vehicle Accident (03/15/2023)",
    "loss_id": 18,
    "claimant": "1 - John Smith",
    "claimant_id": 7,
    "document_description": "Policy Renewal Notice",
    "assigned_to": "Claims Department",
    "assigned_to_id": 3,
    "assigned_to_type": "group",
    "producer_number": "AG-789456",
    "producer_id": 15
  }
}
```

### PUT /api/metadata/documents/{documentId}

Updates metadata for a specific document.

**Path Parameters:**
- `documentId`: Document ID

**Request Body:**
```json
{
  "policy_id": 42,
  "loss_id": 18,
  "claimant_id": 7,
  "document_description": "Policy Renewal Notice",
  "assigned_to_id": 3,
  "assigned_to_type": "group",
  "producer_id": 15
}
```

**Response:**
```json
{
  "data": {
    "policy_number": "PLCY-12345",
    "policy_id": 42,
    "loss_sequence": "1 - Vehicle Accident (03/15/2023)",
    "loss_id": 18,
    "claimant": "1 - John Smith",
    "claimant_id": 7,
    "document_description": "Policy Renewal Notice",
    "assigned_to": "Claims Department",
    "assigned_to_id": 3,
    "assigned_to_type": "group",
    "producer_number": "AG-789456",
    "producer_id": 15
  }
}
```

### GET /api/metadata/options/policies

Retrieves policy options for dropdown selection.

**Query Parameters:**
- `search`: Search term for filtering policies (optional)
- `producer_id`: Filter policies by producer ID (optional)
- `limit`: Maximum number of results to return (default: 10, max: 100)

**Response:**
```json
{
  "data": [
    {
      "id": 42,
      "label": "PLCY-12345",
      "value": 42,
      "metadata": {
        "effective_date": "2023-01-01",
        "expiration_date": "2024-01-01"
      }
    },
    {
      "id": 43,
      "label": "PLCY-12346",
      "value": 43,
      "metadata": {
        "effective_date": "2023-02-15",
        "expiration_date": "2024-02-15"
      }
    }
  ]
}
```

### GET /api/metadata/options/losses/{policyId}

Retrieves loss options for a specific policy.

**Path Parameters:**
- `policyId`: Policy ID

**Query Parameters:**
- `search`: Search term for filtering losses (optional)
- `limit`: Maximum number of results to return (default: 10, max: 100)

**Response:**
```json
{
  "data": [
    {
      "id": 18,
      "label": "1 - Vehicle Accident (03/15/2023)",
      "value": 18,
      "metadata": {
        "date": "2023-03-15",
        "type": "Vehicle Accident"
      }
    },
    {
      "id": 19,
      "label": "2 - Property Damage (04/22/2023)",
      "value": 19,
      "metadata": {
        "date": "2023-04-22",
        "type": "Property Damage"
      }
    }
  ]
}
```

### GET /api/metadata/options/claimants/{lossId}

Retrieves claimant options for a specific loss.

**Path Parameters:**
- `lossId`: Loss ID

**Query Parameters:**
- `search`: Search term for filtering claimants (optional)
- `limit`: Maximum number of results to return (default: 10, max: 100)

**Response:**
```json
{
  "data": [
    {
      "id": 7,
      "label": "1 - John Smith",
      "value": 7,
      "metadata": {
        "type": "Primary"
      }
    },
    {
      "id": 8,
      "label": "2 - Jane Smith",
      "value": 8,
      "metadata": {
        "type": "Secondary"
      }
    }
  ]
}
```

### GET /api/metadata/options/producers

Retrieves producer options for dropdown selection.

**Query Parameters:**
- `search`: Search term for filtering producers (optional)
- `limit`: Maximum number of results to return (default: 10, max: 100)

**Response:**
```json
{
  "data": [
    {
      "id": 15,
      "label": "AG-789456 - Acme Insurance Agency",
      "value": 15,
      "metadata": {
        "number": "AG-789456",
        "name": "Acme Insurance Agency"
      }
    },
    {
      "id": 16,
      "label": "AG-789457 - Best Insurance Brokers",
      "value": 16,
      "metadata": {
        "number": "AG-789457",
        "name": "Best Insurance Brokers"
      }
    }
  ]
}
```

### GET /api/metadata/options/users

Retrieves user options for assignment dropdown.

**Query Parameters:**
- `search`: Search term for filtering users (optional)
- `limit`: Maximum number of results to return (default: 10, max: 100)

**Response:**
```json
{
  "data": [
    {
      "id": 5,
      "label": "Sarah Johnson",
      "value": 5,
      "metadata": {
        "username": "sarah.johnson",
        "email": "sarah.johnson@example.com"
      }
    },
    {
      "id": 6,
      "label": "Michael Brown",
      "value": 6,
      "metadata": {
        "username": "michael.brown",
        "email": "michael.brown@example.com"
      }
    }
  ]
}
```

### GET /api/metadata/options/user-groups

Retrieves user group options for assignment dropdown.

**Query Parameters:**
- `search`: Search term for filtering user groups (optional)
- `limit`: Maximum number of results to return (default: 10, max: 100)

**Response:**
```json
{
  "data": [
    {
      "id": 3,
      "label": "Claims Department",
      "value": 3,
      "metadata": {
        "member_count": 12
      }
    },
    {
      "id": 4,
      "label": "Underwriting Department",
      "value": 4,
      "metadata": {
        "member_count": 8
      }
    }
  ]
}
```

## Navigation Endpoints

Endpoints for retrieving URLs for contextual navigation.

### GET /api/policies/{id}/url

Retrieves the URL for the policy view page.

**Path Parameters:**
- `id`: Policy ID

**Response:**
```json
{
  "data": {
    "url": "/policies/42"
  }
}
```

### GET /api/claimants/{id}/url

Retrieves the URL for the claimant view page.

**Path Parameters:**
- `id`: Claimant ID

**Response:**
```json
{
  "data": {
    "url": "/claimants/7"
  }
}
```

### GET /api/producers/{id}/url

Retrieves the URL for the producer view page.

**Path Parameters:**
- `id`: Producer ID

**Response:**
```json
{
  "data": {
    "url": "/producers/15"
  }
}
```

## Adobe PDF Viewer Integration

The Documents View feature integrates with Adobe Acrobat PDF viewer for document display. This section provides details on how to integrate with the viewer in frontend applications.

### Initialization

Initialize the Adobe PDF viewer with the document URL:
```javascript
const viewerConfig = {
  container: '#pdf-container',
  documentId: 'document-1',
  url: 'http://example.com/api/documents/1/file',
  enableAnnotationAPIs: false,
  enableFormFilling: false,
  enablePrint: true,
  enableDownload: true,
  showAnnotationTools: false,
  showFormFilling: false
};

const adobeDCView = new AdobeDC.View(viewerConfig);
adobeDCView.previewFile();
```

### Event Handling

Listen for viewer events:
```javascript
adobeDCView.registerCallback(
  AdobeDC.View.Enum.CallbackType.EVENT_LISTENER,
  function(event) {
    switch (event.type) {
      case 'DOCUMENT_LOAD_COMPLETE':
        console.log('Document loaded successfully');
        break;
      case 'PAGE_NAVIGATION':
        console.log('Page changed to', event.data.pageNumber);
        break;
      case 'DOCUMENT_DOWNLOAD':
        console.log('Document download initiated');
        break;
    }
  }
);
```

## Example Usage Scenarios

This section provides examples of common API usage scenarios for the Documents View feature.

### Document Viewing Flow

1. Retrieve document details: `GET /api/documents/{id}`
2. Initialize Adobe PDF viewer with document file URL
3. Retrieve document metadata: `GET /api/metadata/documents/{documentId}`
4. Display metadata in the right panel
5. Retrieve document history: `GET /api/documents/{id}/history`

### Metadata Editing Flow

1. Retrieve policy options: `GET /api/metadata/options/policies`
2. When policy is selected, retrieve loss options: `GET /api/metadata/options/losses/{policyId}`
3. When loss is selected, retrieve claimant options: `GET /api/metadata/options/claimants/{lossId}`
4. Retrieve producer options: `GET /api/metadata/options/producers`
5. Update document metadata: `PUT /api/metadata/documents/{documentId}`

### Document Processing Flow

1. Mark document as processed: `POST /api/documents/{id}/process` with `{"process_state": true}`
2. Retrieve updated document details: `GET /api/documents/{id}`
3. Update UI to reflect processed state
4. To unprocess, call: `POST /api/documents/{id}/process` with `{"process_state": false}`

### Document Trashing Flow

1. Move document to trash: `POST /api/documents/{id}/trash`
2. Navigate away from document view
3. Update document list to reflect trashed state

## Security Considerations

This section outlines important security considerations when using the Documents View API.

### Authentication

All API requests must be authenticated using Laravel Sanctum. Include the token in the Authorization header for all requests.

### Authorization

The API enforces role-based access control. Users can only access documents and perform actions based on their assigned permissions.

### Data Protection

All API communication should occur over HTTPS to ensure data encryption in transit. Sensitive document data should not be cached on the client side.

### Audit Logging

All document actions are logged for audit purposes. The API automatically creates audit records for document views, metadata updates, processing actions, and trash operations.

## API Client Implementation

This section provides guidance on implementing an API client for the Documents View feature.

### JavaScript Client Example

```javascript
class DocumentsApiClient {
  constructor(baseUrl, authToken) {
    this.baseUrl = baseUrl;
    this.authToken = authToken;
  }

  async getDocuments(params = {}) {
    return this._request('GET', '/documents', params);
  }

  async getDocument(id) {
    return this._request('GET', `/documents/${id}`);
  }

  async updateDocument(id, data) {
    return this._request('PUT', `/documents/${id}`, null, data);
  }

  async processDocument(id, processState) {
    return this._request('POST', `/documents/${id}/process`, null, { process_state: processState });
  }

  async trashDocument(id) {
    return this._request('POST', `/documents/${id}/trash`);
  }

  async getDocumentHistory(id, params = {}) {
    return this._request('GET', `/documents/${id}/history`, params);
  }

  async getDocumentMetadata(id) {
    return this._request('GET', `/metadata/documents/${id}`);
  }

  async updateDocumentMetadata(id, data) {
    return this._request('PUT', `/metadata/documents/${id}`, null, data);
  }

  async getPolicyOptions(params = {}) {
    return this._request('GET', '/metadata/options/policies', params);
  }

  async getLossOptions(policyId, params = {}) {
    return this._request('GET', `/metadata/options/losses/${policyId}`, params);
  }

  async getClaimantOptions(lossId, params = {}) {
    return this._request('GET', `/metadata/options/claimants/${lossId}`, params);
  }

  async getProducerOptions(params = {}) {
    return this._request('GET', '/metadata/options/producers', params);
  }

  async _request(method, path, params = null, data = null) {
    const url = new URL(`${this.baseUrl}/api${path}`);
    
    if (params) {
      Object.keys(params).forEach(key => {
        url.searchParams.append(key, params[key]);
      });
    }
    
    const options = {
      method,
      headers: {
        'Authorization': `Bearer ${this.authToken}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    };
    
    if (data) {
      options.body = JSON.stringify(data);
    }
    
    const response = await fetch(url, options);
    
    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.error?.message || 'API request failed');
    }
    
    return response.json();
  }
}
```

### Error Handling

Implement proper error handling in your API client:
```javascript
try {
  const document = await apiClient.getDocument(123);
  // Process document data
} catch (error) {
  if (error.response?.status === 404) {
    // Document not found handling
  } else if (error.response?.status === 403) {
    // Permission denied handling
  } else {
    // General error handling
  }
}
```

### Rate Limit Handling

Implement rate limit handling in your API client:
```javascript
async _request(method, path, params = null, data = null) {
  // ... existing code ...
  
  if (response.status === 429) {
    const retryAfter = response.headers.get('Retry-After') || 60;
    console.warn(`Rate limit exceeded. Retry after ${retryAfter} seconds.`);
    // Implement retry logic or notify user
  }
  
  // ... existing code ...
}