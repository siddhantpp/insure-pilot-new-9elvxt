import React from 'react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import HistoryItem from '../../../components/history/HistoryItem';
import { DocumentActionType } from '../../../models/document.types';
import { HistoryEntry } from '../../../models/history.types';
import { createMockDocumentHistory } from '../../../utils/testUtils';

describe('HistoryItem', () => {
  test('renders history entry with correct timestamp', () => {
    const mockEntry: HistoryEntry = {
      id: 1001,
      actionType: DocumentActionType.VIEW,
      description: '',
      timestamp: '2023-05-12T10:45:00Z',
      formattedTimestamp: '05/12/2023 10:45 AM',
      user: {
        id: 1,
        username: 'testuser'
      }
    };

    render(<HistoryItem entry={mockEntry} />);
    expect(screen.getByText('05/12/2023 10:45 AM')).toBeInTheDocument();
  });

  test('renders history entry with correct username', () => {
    const mockEntry: HistoryEntry = {
      id: 1001,
      actionType: DocumentActionType.VIEW,
      description: '',
      timestamp: '2023-05-12T10:45:00Z',
      formattedTimestamp: '05/12/2023 10:45 AM',
      user: {
        id: 1,
        username: 'testuser'
      }
    };

    render(<HistoryItem entry={mockEntry} />);
    expect(screen.getByText('testuser')).toBeInTheDocument();
  });

  test('renders correct action label for VIEW action', () => {
    const mockEntry: HistoryEntry = {
      id: 1001,
      actionType: DocumentActionType.VIEW,
      description: '',
      timestamp: '2023-05-12T10:45:00Z',
      formattedTimestamp: '05/12/2023 10:45 AM',
      user: {
        id: 1,
        username: 'testuser'
      }
    };

    render(<HistoryItem entry={mockEntry} />);
    expect(screen.getByText('Document viewed')).toBeInTheDocument();
  });

  test('renders correct action label for CREATE action', () => {
    const mockEntry: HistoryEntry = {
      id: 1001,
      actionType: DocumentActionType.CREATE,
      description: '',
      timestamp: '2023-05-12T10:45:00Z',
      formattedTimestamp: '05/12/2023 10:45 AM',
      user: {
        id: 1,
        username: 'testuser'
      }
    };

    render(<HistoryItem entry={mockEntry} />);
    expect(screen.getByText('Document uploaded')).toBeInTheDocument();
  });

  test('renders correct action label for UPDATE_METADATA action', () => {
    const mockEntry: HistoryEntry = {
      id: 1001,
      actionType: DocumentActionType.UPDATE_METADATA,
      description: '',
      timestamp: '2023-05-12T10:45:00Z',
      formattedTimestamp: '05/12/2023 10:45 AM',
      user: {
        id: 1,
        username: 'testuser'
      }
    };

    render(<HistoryItem entry={mockEntry} />);
    expect(screen.getByText('Changed')).toBeInTheDocument();
  });

  test('renders correct action label for PROCESS action', () => {
    const mockEntry: HistoryEntry = {
      id: 1001,
      actionType: DocumentActionType.PROCESS,
      description: '',
      timestamp: '2023-05-12T10:45:00Z',
      formattedTimestamp: '05/12/2023 10:45 AM',
      user: {
        id: 1,
        username: 'testuser'
      }
    };

    render(<HistoryItem entry={mockEntry} />);
    expect(screen.getByText('Marked as processed')).toBeInTheDocument();
  });

  test('renders correct action label for UNPROCESS action', () => {
    const mockEntry: HistoryEntry = {
      id: 1001,
      actionType: DocumentActionType.UNPROCESS,
      description: '',
      timestamp: '2023-05-12T10:45:00Z',
      formattedTimestamp: '05/12/2023 10:45 AM',
      user: {
        id: 1,
        username: 'testuser'
      }
    };

    render(<HistoryItem entry={mockEntry} />);
    expect(screen.getByText('Unmarked as processed')).toBeInTheDocument();
  });

  test('renders correct action label for TRASH action', () => {
    const mockEntry: HistoryEntry = {
      id: 1001,
      actionType: DocumentActionType.TRASH,
      description: '',
      timestamp: '2023-05-12T10:45:00Z',
      formattedTimestamp: '05/12/2023 10:45 AM',
      user: {
        id: 1,
        username: 'testuser'
      }
    };

    render(<HistoryItem entry={mockEntry} />);
    expect(screen.getByText('Moved to trash')).toBeInTheDocument();
  });

  test('renders correct action label for RESTORE action', () => {
    const mockEntry: HistoryEntry = {
      id: 1001,
      actionType: DocumentActionType.RESTORE,
      description: '',
      timestamp: '2023-05-12T10:45:00Z',
      formattedTimestamp: '05/12/2023 10:45 AM',
      user: {
        id: 1,
        username: 'testuser'
      }
    };

    render(<HistoryItem entry={mockEntry} />);
    expect(screen.getByText('Restored from trash')).toBeInTheDocument();
  });

  test('renders description when provided', () => {
    const mockEntry: HistoryEntry = {
      id: 1001,
      actionType: DocumentActionType.UPDATE_METADATA,
      description: 'Changed Document Description from "Policy Document" to "Policy Renewal Notice"',
      timestamp: '2023-05-12T10:45:00Z',
      formattedTimestamp: '05/12/2023 10:45 AM',
      user: {
        id: 1,
        username: 'testuser'
      }
    };

    render(<HistoryItem entry={mockEntry} />);
    expect(screen.getByText('Changed Document Description from "Policy Document" to "Policy Renewal Notice"')).toBeInTheDocument();
  });

  test('applies correct icon for different action types', () => {
    // Test VIEW action
    const viewEntry: HistoryEntry = {
      id: 1001,
      actionType: DocumentActionType.VIEW,
      description: '',
      timestamp: '2023-05-12T10:45:00Z',
      formattedTimestamp: '05/12/2023 10:45 AM',
      user: { id: 1, username: 'testuser' }
    };
    const { rerender, container } = render(<HistoryItem entry={viewEntry} />);
    expect(container.querySelector('.icon-eye')).toBeInTheDocument();

    // Test CREATE action
    const createEntry: HistoryEntry = {
      id: 1002,
      actionType: DocumentActionType.CREATE,
      description: '',
      timestamp: '2023-05-12T10:45:00Z',
      formattedTimestamp: '05/12/2023 10:45 AM',
      user: { id: 1, username: 'testuser' }
    };
    rerender(<HistoryItem entry={createEntry} />);
    expect(container.querySelector('.icon-plus-circle')).toBeInTheDocument();

    // Test PROCESS action
    const processEntry: HistoryEntry = {
      id: 1003,
      actionType: DocumentActionType.PROCESS,
      description: '',
      timestamp: '2023-05-12T10:45:00Z',
      formattedTimestamp: '05/12/2023 10:45 AM',
      user: { id: 1, username: 'testuser' }
    };
    rerender(<HistoryItem entry={processEntry} />);
    expect(container.querySelector('.icon-check-circle')).toBeInTheDocument();
  });

  test('has appropriate aria attributes', () => {
    const mockEntry: HistoryEntry = {
      id: 1001,
      actionType: DocumentActionType.VIEW,
      description: '',
      timestamp: '2023-05-12T10:45:00Z',
      formattedTimestamp: '05/12/2023 10:45 AM',
      user: {
        id: 1,
        username: 'testuser'
      }
    };

    const { container } = render(<HistoryItem entry={mockEntry} />);
    const historyItem = screen.getByRole('listitem');
    expect(historyItem).toBeInTheDocument();
    
    const icon = container.querySelector('.history-item-icon');
    expect(icon).toHaveAttribute('aria-hidden', 'true');
  });
});