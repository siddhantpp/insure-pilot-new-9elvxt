import React from 'react';
import { screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ActionButton } from '../../../components/buttons/ActionButton';
import { renderWithProviders } from '../../../utils/testUtils';

describe('ActionButton', () => {
  test('renders with default props', () => {
    renderWithProviders(
      <ActionButton>Test Button</ActionButton>
    );
    
    const button = screen.getByRole('button', { name: /test button/i });
    expect(button).toBeInTheDocument();
    expect(button).toHaveClass('action-button');
    expect(button).toHaveClass('action-button--primary'); // primary is default variant
  });

  test('renders with primary variant', () => {
    renderWithProviders(
      <ActionButton variant="primary">Primary Button</ActionButton>
    );
    
    const button = screen.getByRole('button', { name: /primary button/i });
    expect(button).toHaveClass('action-button--primary');
  });

  test('renders with secondary variant', () => {
    renderWithProviders(
      <ActionButton variant="secondary">Secondary Button</ActionButton>
    );
    
    const button = screen.getByRole('button', { name: /secondary button/i });
    expect(button).toHaveClass('action-button--secondary');
  });

  test('renders with danger variant', () => {
    renderWithProviders(
      <ActionButton variant="danger">Danger Button</ActionButton>
    );
    
    const button = screen.getByRole('button', { name: /danger button/i });
    expect(button).toHaveClass('action-button--danger');
  });

  test('renders in loading state', () => {
    const { container } = renderWithProviders(
      <ActionButton isLoading={true}>Loading Button</ActionButton>
    );
    
    const button = screen.getByRole('button');
    expect(button).toHaveClass('action-button--loading');
    expect(button).toBeDisabled();
    
    // Check for loading indicator
    const loadingIndicator = container.querySelector('.action-button__loading-indicator');
    expect(loadingIndicator).toBeInTheDocument();
    
    // Text should be present but may be styled differently
    const buttonText = container.querySelector('.action-button__text--loading');
    expect(buttonText).toBeInTheDocument();
    expect(buttonText).toHaveTextContent('Loading Button');
  });

  test('renders in processed state', () => {
    renderWithProviders(
      <ActionButton isProcessed={true}>Processed Button</ActionButton>
    );
    
    const button = screen.getByRole('button', { name: /processed button/i });
    expect(button).toHaveClass('action-button--processed');
    expect(button).toHaveAttribute('aria-pressed', 'true');
  });

  test('renders in disabled state', () => {
    renderWithProviders(
      <ActionButton disabled={true}>Disabled Button</ActionButton>
    );
    
    const button = screen.getByRole('button', { name: /disabled button/i });
    expect(button).toBeDisabled();
    expect(button).toHaveClass('action-button--disabled');
  });

  test('applies custom className', () => {
    renderWithProviders(
      <ActionButton className="custom-class">Custom Button</ActionButton>
    );
    
    const button = screen.getByRole('button', { name: /custom button/i });
    expect(button).toHaveClass('custom-class');
  });

  test('handles click events', () => {
    const handleClick = jest.fn();
    
    renderWithProviders(
      <ActionButton onClick={handleClick}>Click Button</ActionButton>
    );
    
    const button = screen.getByRole('button', { name: /click button/i });
    fireEvent.click(button);
    
    expect(handleClick).toHaveBeenCalledTimes(1);
  });

  test('does not trigger click when disabled', () => {
    const handleClick = jest.fn();
    
    renderWithProviders(
      <ActionButton onClick={handleClick} disabled={true}>Disabled Button</ActionButton>
    );
    
    const button = screen.getByRole('button', { name: /disabled button/i });
    fireEvent.click(button);
    
    expect(handleClick).not.toHaveBeenCalled();
  });

  test('does not trigger click when loading', () => {
    const handleClick = jest.fn();
    
    renderWithProviders(
      <ActionButton onClick={handleClick} isLoading={true}>Loading Button</ActionButton>
    );
    
    const button = screen.getByRole('button');
    fireEvent.click(button);
    
    expect(handleClick).not.toHaveBeenCalled();
  });

  test('has correct accessibility attributes', () => {
    renderWithProviders(
      <ActionButton aria-label="Test Button">Accessible Button</ActionButton>
    );
    
    const button = screen.getByRole('button', { name: /accessible button/i });
    expect(button).toHaveAttribute('aria-label', 'Test Button');
    expect(button).toHaveAttribute('type', 'button'); // Ensures it doesn't submit forms unintentionally
  });

  test('can be triggered with keyboard', async () => {
    const handleClick = jest.fn();
    const user = userEvent.setup();
    
    renderWithProviders(
      <ActionButton onClick={handleClick}>Keyboard Button</ActionButton>
    );
    
    const button = screen.getByRole('button', { name: /keyboard button/i });
    
    // Focus and press Enter key
    button.focus();
    await user.keyboard('{Enter}');
    expect(handleClick).toHaveBeenCalledTimes(1);
    
    // Press Space key
    await user.keyboard(' ');
    expect(handleClick).toHaveBeenCalledTimes(2);
  });
});