import React from 'react';
import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import DropdownControl from '../../../components/dropdown/DropdownControl';
import { DropdownProps } from '../../../models/dropdown.types';
import { DropdownOption } from '../../../models/api.types';
import { renderWithProviders } from '../../../utils/testUtils';

/**
 * Helper function to set up the component for testing with default props
 */
const setup = (props: Partial<DropdownProps> = {}) => {
  // Create default options
  const options: DropdownOption[] = [
    { id: 1, label: 'Option 1', value: 1 },
    { id: 2, label: 'Option 2', value: 2 },
    { id: 3, label: 'Option 3', value: 3 }
  ];

  // Create mock handlers
  const onChange = jest.fn();
  const onBlur = jest.fn();

  // Default props with mocks
  const defaultProps: DropdownProps = {
    name: 'test-dropdown',
    label: 'Test Dropdown',
    value: null,
    options,
    onChange,
    onBlur,
    error: null,
    disabled: false,
    placeholder: 'Select an option',
    dependsOn: null,
    isLoading: false,
    isReadOnly: false
  };

  // Render with merged props
  const utils = renderWithProviders(
    <DropdownControl {...defaultProps} {...props} />
  );

  return {
    ...utils,
    options,
    onChange,
    onBlur
  };
};

/**
 * Helper function to create mock dropdown options for testing
 */
const createMockOptions = (count: number): DropdownOption[] => {
  const options: DropdownOption[] = [];
  for (let i = 0; i < count; i++) {
    options.push({
      id: i + 1,
      label: `Option ${i + 1}`,
      value: i + 1
    });
  }
  return options;
};

describe('DropdownControl', () => {
  it('renders correctly with default props', () => {
    setup();
    const input = screen.getByTestId('dropdown-input-test-dropdown');
    expect(input).toBeInTheDocument();
    expect(screen.queryByTestId('options-menu')).not.toBeInTheDocument();
    expect(input).toHaveAttribute('placeholder', 'Select an option');
  });

  it('displays the selected value when provided', () => {
    const options = createMockOptions(3);
    setup({ value: 2, options });
    expect(screen.getByTestId('dropdown-input-test-dropdown')).toHaveValue('Option 2');
  });

  it('opens dropdown on input focus', async () => {
    const { user } = setup();
    const input = screen.getByTestId('dropdown-input-test-dropdown');
    
    await user.click(input);
    
    expect(screen.getByTestId('options-menu')).toBeInTheDocument();
    expect(screen.getAllByRole('option').length).toBe(3);
  });

  it('filters options based on input text', async () => {
    const { user } = setup();
    const input = screen.getByTestId('dropdown-input-test-dropdown');
    
    await user.click(input);
    await user.type(input, '1');
    
    await waitFor(() => {
      expect(screen.getAllByRole('option').length).toBe(1);
      expect(screen.getByText('Option 1')).toBeInTheDocument();
    });

    // Test with input that doesn't match any options
    await user.clear(input);
    await user.type(input, 'xyz');
    
    await waitFor(() => {
      expect(screen.getByText('No options available')).toBeInTheDocument();
    });
  });

  it('selects an option when clicked', async () => {
    const { user, onChange } = setup();
    const input = screen.getByTestId('dropdown-input-test-dropdown');
    
    await user.click(input);
    await user.click(screen.getByText('Option 2'));
    
    expect(onChange).toHaveBeenCalledWith(2);
    expect(screen.queryByTestId('options-menu')).not.toBeInTheDocument();
    expect(input).toHaveValue('Option 2');
  });

  it('handles keyboard navigation correctly', async () => {
    const { user, onChange } = setup();
    const input = screen.getByTestId('dropdown-input-test-dropdown');
    
    await user.click(input);
    
    // Press arrow down to highlight first option
    await user.keyboard('{ArrowDown}');
    expect(screen.getAllByRole('option')[0]).toHaveAttribute('aria-selected', 'true');
    
    // Press arrow down again to highlight second option
    await user.keyboard('{ArrowDown}');
    expect(screen.getAllByRole('option')[1]).toHaveAttribute('aria-selected', 'true');
    
    // Press arrow up to highlight first option again
    await user.keyboard('{ArrowUp}');
    expect(screen.getAllByRole('option')[0]).toHaveAttribute('aria-selected', 'true');
    
    // Press Enter to select the highlighted option
    await user.keyboard('{Enter}');
    expect(onChange).toHaveBeenCalledWith(1);
    expect(screen.queryByTestId('options-menu')).not.toBeInTheDocument();
  });

  it('closes dropdown when clicking outside', async () => {
    const { user, onBlur } = setup();
    const input = screen.getByTestId('dropdown-input-test-dropdown');
    
    await user.click(input);
    expect(screen.getByTestId('options-menu')).toBeInTheDocument();
    
    // Click outside the dropdown
    await user.click(document.body);
    
    await waitFor(() => {
      expect(screen.queryByTestId('options-menu')).not.toBeInTheDocument();
      expect(onBlur).toHaveBeenCalled();
    });
  });

  it('displays validation error when provided', () => {
    setup({ error: 'This field is required' });
    
    expect(screen.getByText('This field is required')).toBeInTheDocument();
    expect(screen.getByTestId('dropdown-input-test-dropdown')).toHaveAttribute('aria-invalid', 'true');
  });

  it('disables the dropdown when disabled prop is true', async () => {
    const { user } = setup({ disabled: true });
    const input = screen.getByTestId('dropdown-input-test-dropdown');
    
    expect(input).toBeDisabled();
    
    await user.click(input);
    expect(screen.queryByTestId('options-menu')).not.toBeInTheDocument();
  });

  it('resets dependent field when dependsOn value changes', () => {
    // Create a spy for the onChange function
    const onChange = jest.fn();
    
    // Render the component with a dependsOn prop
    const { rerender } = renderWithProviders(
      <DropdownControl
        name="test-dropdown"
        label="Test Dropdown"
        value={2}
        options={createMockOptions(3)}
        onChange={onChange}
        onBlur={jest.fn()}
        error={null}
        disabled={false}
        placeholder="Select an option"
        dependsOn="parent-field"
        isLoading={false}
        isReadOnly={false}
      />
    );
    
    // Re-render with a different dependsOn value to trigger the useEffect
    rerender(
      <DropdownControl
        name="test-dropdown"
        label="Test Dropdown"
        value={2}
        options={createMockOptions(3)}
        onChange={onChange}
        onBlur={jest.fn()}
        error={null}
        disabled={false}
        placeholder="Select an option"
        dependsOn="parent-field-changed" // Changed value
        isLoading={false}
        isReadOnly={false}
      />
    );
    
    // Check if onChange was called with null (resetting the field)
    expect(onChange).toHaveBeenCalledWith(null);
  });

  it('shows loading state when isLoading prop is true', async () => {
    const { user } = setup({ isLoading: true });
    const input = screen.getByTestId('dropdown-input-test-dropdown');
    
    await user.click(input);
    
    const optionsMenu = screen.getByTestId('options-menu');
    expect(optionsMenu).toBeInTheDocument();
    expect(screen.getByText('Loading options...')).toBeInTheDocument();
  });

  it('applies proper ARIA attributes for accessibility', async () => {
    const { user } = setup();
    const input = screen.getByTestId('dropdown-input-test-dropdown');
    
    // Check initial ARIA attributes
    expect(input).toHaveAttribute('aria-haspopup', 'listbox');
    expect(input).toHaveAttribute('aria-expanded', 'false');
    expect(input).toHaveAttribute('role', 'combobox');
    
    // Open dropdown and check updated ARIA attributes
    await user.click(input);
    
    expect(input).toHaveAttribute('aria-expanded', 'true');
    expect(input).toHaveAttribute('aria-controls');
    
    const optionsMenu = screen.getByTestId('options-menu');
    expect(optionsMenu).toBeInTheDocument();
    expect(optionsMenu).toHaveAttribute('aria-label', 'Options');
  });

  it('handles read-only state correctly', async () => {
    const { user } = setup({ isReadOnly: true, value: 2 });
    const input = screen.getByTestId('dropdown-input-test-dropdown');
    
    expect(input).toHaveAttribute('readOnly');
    expect(input).toHaveValue('Option 2');
    
    await user.click(input);
    expect(screen.queryByTestId('options-menu')).not.toBeInTheDocument();
  });
});