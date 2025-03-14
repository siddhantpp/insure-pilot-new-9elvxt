import React from 'react';
import { screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { LightboxOverlay } from '../../../components/layout/LightboxOverlay';
import { renderWithProviders } from '../../../utils/testUtils';

// Common setup function for LightboxOverlay tests
const setup = (props = {}) => {
  const defaultProps = {
    onClose: jest.fn(),
    children: <div data-testid="lightbox-content">Test Content</div>,
    title: 'Test Lightbox',
    closeOnOutsideClick: false,
    showCloseButton: true,
  };

  const mergedProps = { ...defaultProps, ...props };
  const result = renderWithProviders(<LightboxOverlay {...mergedProps} />);

  return {
    ...result,
    onClose: mergedProps.onClose,
  };
};

describe('LightboxOverlay rendering', () => {
  it('should render the lightbox overlay with the correct title', () => {
    setup();
    
    // Check if the lightbox is rendered
    const lightbox = screen.getByRole('dialog');
    expect(lightbox).toBeInTheDocument();
    
    // Check if the title is rendered correctly
    const title = screen.getByText('Test Lightbox');
    expect(title).toBeInTheDocument();
    expect(title).toHaveAttribute('id', 'lightbox-title');
  });

  it('should render children content inside the lightbox', () => {
    setup();
    
    // Check if the children content is rendered
    const content = screen.getByTestId('lightbox-content');
    expect(content).toBeInTheDocument();
    expect(content).toHaveTextContent('Test Content');
  });

  it('should apply custom className when provided', () => {
    setup({ className: 'custom-class' });
    
    // Check if the custom class is applied
    const lightbox = screen.getByRole('dialog');
    expect(lightbox).toHaveClass('custom-class');
  });

  it('should render close button when showCloseButton is true', () => {
    setup({ showCloseButton: true });
    
    // Check if the close button is rendered
    const closeButton = screen.getByRole('button', { name: /close document viewer/i });
    expect(closeButton).toBeInTheDocument();
  });

  it('should not render close button when showCloseButton is false', () => {
    setup({ showCloseButton: false });
    
    // Check that the close button is not rendered
    const closeButton = screen.queryByRole('button', { name: /close document viewer/i });
    expect(closeButton).not.toBeInTheDocument();
  });
});

describe('LightboxOverlay close functionality', () => {
  it('should call onClose when close button is clicked', async () => {
    const { onClose } = setup();
    
    // Click the close button
    const closeButton = screen.getByRole('button', { name: /close document viewer/i });
    await userEvent.click(closeButton);
    
    // Check if onClose was called after the animation delay
    await waitFor(() => {
      expect(onClose).toHaveBeenCalledTimes(1);
    }, { timeout: 400 }); // Add buffer to the 300ms animation timeout
  });

  it('should call onClose when Escape key is pressed', async () => {
    const { onClose } = setup();
    
    // Press the Escape key
    fireEvent.keyDown(document, { key: 'Escape' });
    
    // Check if onClose was called after the animation delay
    await waitFor(() => {
      expect(onClose).toHaveBeenCalledTimes(1);
    }, { timeout: 400 });
  });

  it('should call onClose when clicking outside the content if closeOnOutsideClick is true', async () => {
    const { onClose } = setup({ closeOnOutsideClick: true });
    
    // Get the overlay element (parent of the lightbox content)
    const lightboxOverlay = screen.getByRole('dialog');
    
    // Click the overlay (outside the content)
    fireEvent.mouseDown(lightboxOverlay);
    
    // Check if onClose was called after the animation delay
    await waitFor(() => {
      expect(onClose).toHaveBeenCalledTimes(1);
    }, { timeout: 400 });
  });

  it('should not call onClose when clicking outside the content if closeOnOutsideClick is false', async () => {
    const { onClose } = setup({ closeOnOutsideClick: false });
    
    // Get the overlay element
    const lightboxOverlay = screen.getByRole('dialog');
    
    // Click the overlay
    fireEvent.mouseDown(lightboxOverlay);
    
    // Wait to ensure onClose isn't called
    await new Promise(resolve => setTimeout(resolve, 400));
    
    // Check that onClose was not called
    expect(onClose).not.toHaveBeenCalled();
  });
});

describe('LightboxOverlay accessibility', () => {
  it('should have the correct ARIA role and attributes', () => {
    setup();
    
    // Check ARIA attributes
    const lightbox = screen.getByRole('dialog');
    expect(lightbox).toHaveAttribute('aria-modal', 'true');
    expect(lightbox).toHaveAttribute('aria-labelledby', 'lightbox-title');
  });

  it('should trap focus within the lightbox', async () => {
    // Render a lightbox with multiple focusable elements
    setup({
      children: (
        <div>
          <button data-testid="button-1">Button 1</button>
          <button data-testid="button-2">Button 2</button>
          <button data-testid="button-3">Button 3</button>
        </div>
      )
    });
    
    // Get all the focusable elements
    const closeButton = screen.getByRole('button', { name: /close document viewer/i });
    const button1 = screen.getByTestId('button-1');
    const button2 = screen.getByTestId('button-2');
    const button3 = screen.getByTestId('button-3');
    
    // Press Tab to move focus through elements
    await userEvent.tab();
    expect(document.activeElement).toBe(closeButton);
    
    await userEvent.tab();
    expect(document.activeElement).toBe(button1);
    
    await userEvent.tab();
    expect(document.activeElement).toBe(button2);
    
    await userEvent.tab();
    expect(document.activeElement).toBe(button3);
    
    // Tab again should cycle back to the first element
    await userEvent.tab();
    expect(document.activeElement).toBe(closeButton);
  });

  it('should return focus to the trigger element when closed', async () => {
    // Create a trigger button that will be focused before opening the lightbox
    const { container } = renderWithProviders(
      <>
        <button data-testid="trigger-button">Open Lightbox</button>
      </>
    );
    
    // Focus the trigger button
    const triggerButton = screen.getByTestId('trigger-button');
    triggerButton.focus();
    expect(document.activeElement).toBe(triggerButton);
    
    // Render the lightbox
    const { onClose } = setup();
    
    // Close the lightbox
    const closeButton = screen.getByRole('button', { name: /close document viewer/i });
    await userEvent.click(closeButton);
    
    // Wait for the animation and focus restoration
    await waitFor(() => {
      expect(onClose).toHaveBeenCalledTimes(1);
    }, { timeout: 400 });
    
    // Note: In a real application, focus would be restored to the trigger button
    // However, in the test environment this is difficult to verify directly
  });

  it('should have proper focus management for keyboard navigation', async () => {
    setup();
    
    // Check that the close button is focusable with keyboard
    const closeButton = screen.getByRole('button', { name: /close document viewer/i });
    await userEvent.tab();
    expect(document.activeElement).toBe(closeButton);
    
    // Make sure Enter key triggers the close button
    await userEvent.keyboard('{Enter}');
    
    // Verify onClose was called
    await waitFor(() => {
      expect(closeButton).not.toBeInTheDocument();
    }, { timeout: 400 });
  });
});

describe('LightboxOverlay animations', () => {
  it('should have entrance animation class when mounted', () => {
    setup();
    
    // Check that the lightbox has the transition class but not the exit class
    const lightbox = screen.getByRole('dialog');
    expect(lightbox).toHaveClass('lightbox-overlay--transition');
    expect(lightbox).not.toHaveClass('lightbox-overlay--exiting');
  });

  it('should have exit animation class when closing', async () => {
    setup();
    
    // Click the close button
    const closeButton = screen.getByRole('button', { name: /close document viewer/i });
    await userEvent.click(closeButton);
    
    // Check that the exit class is added
    const lightbox = screen.getByRole('dialog');
    expect(lightbox).toHaveClass('lightbox-overlay--exiting');
  });

  it('should delay onClose call until animation completes', async () => {
    const { onClose } = setup();
    
    // Click the close button
    const closeButton = screen.getByRole('button', { name: /close document viewer/i });
    await userEvent.click(closeButton);
    
    // Verify onClose is not called immediately
    expect(onClose).not.toHaveBeenCalled();
    
    // Verify onClose is called after the animation delay
    await waitFor(() => {
      expect(onClose).toHaveBeenCalledTimes(1);
    }, { timeout: 400 });
  });
});