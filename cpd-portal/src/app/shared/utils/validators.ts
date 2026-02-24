import { AbstractControl, ValidatorFn } from '@angular/forms';

/**
 * Validates that two form controls have matching values.
 * Typically used to confirm a password field matches a confirmation field.
 */
export function passwordMatchValidator(controlName: string, checkControlName: string): ValidatorFn {
  return (control: AbstractControl): { [key: string]: boolean } | null => {
    const controlValue = control.get(controlName);
    const checkControlValue = control.get(checkControlName);

    if (!controlValue || !checkControlValue) {
      return null;
    }

    if (checkControlValue.errors && !checkControlValue.errors['passwordMatch']) {
      return null;
    }

    if (controlValue.value !== checkControlValue.value) {
      checkControlValue.setErrors({ passwordMatch: true });
      return { passwordMatch: true };
    } else {
      checkControlValue.setErrors(null);
      return null;
    }
  };
}
