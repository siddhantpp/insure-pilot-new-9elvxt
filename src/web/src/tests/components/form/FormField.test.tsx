import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import FormField from '../../../components/form/FormField';
import { setAriaAttributes } from '../../../utils/accessibilityUtils';
import { renderWithProviders } from '../../../utils/testUtils';

// Mock the accessibilityUtils module
jest.mock('../../../utils/accessibilityUtils', () => ({
  setAriaAttributes: jest.fn()
}));

describe('FormField component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('renders with label and input', () => {
    render(
      <FormField id="test-field" name="testField" label="Test Label">
        <input id="test-field" />
      </FormField>
    );

    // Verify label and input are rendered
    expect(screen.getByText('Test Label')).toBeInTheDocument();
    expect(screen.getByRole('textbox')).toBeInTheDocument();
    
    // Verify label is associated with input
    const label = screen.getByText('Test Label');
    expect(label).toHaveAttribute('for', 'test-field');
  });

  test('renders with error state', () => {
    render(
      <FormField 
        id="test-field" 
        name="testField" 
        label="Test Label" 
        error="This field has an error"
      >
        <input id="test-field" />
      </FormField>
    );

    // Verify error message is displayed
    expect(screen.getByText('This field has an error')).toBeInTheDocument();
    
    // Verify form group has error class
    const formGroup = screen.getByText('This field has an error').closest('.form-group');
    expect(formGroup).toHaveClass('has-error');
    
    // Verify aria attributes are set correctly
    expect(setAriaAttributes).toHaveBeenCalledWith(
      expect.any(HTMLElement),
      expect.objectContaining({ 
        invalid: true,
        describedBy: 'test-field-error'
      })
    );
  });

  test('renders in disabled state', () => {
    render(
      <FormField 
        id="test-field" 
        name="testField" 
        label="Test Label" 
        disabled
      >
        <input id="test-field" disabled />
      </FormField>
    );

    // Verify input is disabled
    expect(screen.getByRole('textbox')).toBeDisabled();
    
    // Verify form group has disabled class
    const formGroup = screen.getByText('Test Label').closest('.form-group');
    expect(formGroup).toHaveClass('disabled');
  });

  test('renders in read-only state', () => {
    render(
      <FormField 
        id="test-field" 
        name="testField" 
        label="Test Label" 
        readOnly
        value="Read Only Value"
      >
        <input id="test-field" />
      </FormField>
    );

    // Verify ReadOnlyField is rendered with correct values
    expect(screen.getByTestId('read-only-field')).toBeInTheDocument();
    expect(screen.getByText('Test Label')).toBeInTheDocument();
    expect(screen.getByText('Read Only Value')).toBeInTheDocument();
  });

  test('sets ARIA attributes on input', () => {
    render(
      <FormField 
        id="test-field" 
        name="testField" 
        label="Test Label" 
        required
        error="Error message"
      >
        <input id="test-field" />
      </FormField>
    );

    // Verify setAriaAttributes is called with correct parameters
    expect(setAriaAttributes).toHaveBeenCalledWith(
      expect.any(HTMLElement),
      expect.objectContaining({
        invalid: true,
        required: true,
        describedBy: 'test-field-error'
      })
    );
  });

  test('applies custom className', () => {
    const { container } = render(
      <FormField 
        id="test-field" 
        name="testField" 
        label="Test Label" 
        className="custom-class"
      >
        <input id="test-field" />
      </FormField>
    );

    // Find the form-field class element and check for custom class
    const formField = container.querySelector('.form-field');
    expect(formField).toHaveClass('custom-class');
  });

  test('renders with required indicator', () => {
    render(
      <FormField 
        id="test-field" 
        name="testField" 
        label="Test Label" 
        required
      >
        <input id="test-field" />
      </FormField>
    );

    // Verify label has required class
    const label = screen.getByTestId('form-label');
    expect(label).toHaveClass('form-label-required');
    
    // Verify setAriaAttributes is called with required=true
    expect(setAriaAttributes).toHaveBeenCalledWith(
      expect.any(HTMLElement),
      expect.objectContaining({ required: true })
    );
  });

  test('renders in inline layout', () => {
    const { container } = render(
      <FormField 
        id="test-field" 
        name="testField" 
        label="Test Label" 
        inline
      >
        <input id="test-field" />
      </FormField>
    );

    // Find form-group element and check for inline class
    const formGroup = container.querySelector('.form-group');
    expect(formGroup).toHaveClass('form-group-inline');
  });
});