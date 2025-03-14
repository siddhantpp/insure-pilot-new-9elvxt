import { useState, useCallback } from 'react';
import { DocumentMetadata } from '../models/document.types';
import { MetadataFieldName } from '../models/metadata.types';
import { validateMetadataField, validateMetadataForm } from '../lib/validators';

/**
 * Interface defining the return type of the useFormValidation hook
 */
export interface FormValidationResult {
  values: DocumentMetadata;
  errors: Record<string, string | null>;
  touched: Record<string, boolean>;
  isValid: boolean;
  setFieldValue: (field: string, value: any) => void;
  setFieldTouched: (field: string, isTouched?: boolean) => void;
  setFieldError: (field: string, error: string | null) => void;
  validateField: (field: string) => boolean;
  validateForm: () => boolean;
  resetForm: () => void;
}

/**
 * A custom hook that provides form validation functionality for document metadata
 * @param initialValues Initial document metadata values
 * @returns Form validation state and methods
 */
const useFormValidation = (initialValues: DocumentMetadata): FormValidationResult => {
  // Initialize state for form values, errors, and touched fields
  const [values, setValues] = useState<DocumentMetadata>(initialValues);
  const [errors, setErrors] = useState<Record<string, string | null>>({});
  const [touched, setTouched] = useState<Record<string, boolean>>({});

  /**
   * Validates a single field and updates errors state
   * @param field The field name to validate
   * @returns True if field is valid, false otherwise
   */
  const validateField = useCallback((field: string) => {
    const error = validateMetadataField(
      field as MetadataFieldName,
      values[field as keyof DocumentMetadata],
      values
    );
    
    setErrors(prevErrors => ({
      ...prevErrors,
      [field]: error
    }));
    
    return !error;
  }, [values]);

  /**
   * Updates a field value and validates it
   * @param field The field name to update
   * @param value The new value for the field
   */
  const setFieldValue = useCallback((field: string, value: any) => {
    setValues(prevValues => {
      const newValues = { ...prevValues, [field]: value };
      
      // Validate the field with the new value
      const error = validateMetadataField(
        field as MetadataFieldName,
        value,
        newValues
      );
      
      // Update errors state with validation result
      setErrors(prevErrors => ({
        ...prevErrors,
        [field]: error
      }));
      
      return newValues;
    });
  }, []);

  /**
   * Marks a field as touched, triggering validation
   * @param field The field name to mark as touched
   * @param isTouched Whether the field is touched or not
   */
  const setFieldTouched = useCallback((field: string, isTouched = true) => {
    setTouched(prevTouched => ({
      ...prevTouched,
      [field]: isTouched
    }));
    
    // If field is touched, validate it
    if (isTouched) {
      validateField(field);
    }
  }, [validateField]);

  /**
   * Manually sets an error message for a field
   * @param field The field name to set error for
   * @param error The error message or null to clear
   */
  const setFieldError = useCallback((field: string, error: string | null) => {
    setErrors(prevErrors => ({
      ...prevErrors,
      [field]: error
    }));
  }, []);

  /**
   * Validates all form fields and updates errors state
   * @returns True if form is valid, false otherwise
   */
  const validateForm = useCallback(() => {
    const formErrors = validateMetadataForm(values);
    setErrors(formErrors);
    
    // Mark all fields as touched
    const allTouched: Record<string, boolean> = {};
    Object.keys(formErrors).forEach(field => {
      allTouched[field] = true;
    });
    setTouched(prevTouched => ({
      ...prevTouched,
      ...allTouched
    }));
    
    // Form is valid if no errors exist
    return Object.values(formErrors).every(error => !error);
  }, [values]);

  /**
   * Resets form to initial values and clears errors and touched state
   */
  const resetForm = useCallback(() => {
    setValues(initialValues);
    setErrors({});
    setTouched({});
  }, [initialValues]);

  // Computed property to check if form is valid
  const isValid = Object.values(errors).every(error => !error);

  return {
    values,
    errors,
    touched,
    isValid,
    setFieldValue,
    setFieldTouched,
    setFieldError,
    validateField,
    validateForm,
    resetForm
  };
};

export default useFormValidation;