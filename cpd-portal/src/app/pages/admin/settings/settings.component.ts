import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { SettingsService, PlatformSettings } from '../../../core/services/settings.service';
import { ToastService } from '../../../core/services/toast.service';

@Component({
  selector: 'app-settings',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './settings.component.html',
  styleUrls: ['./settings.component.css'],
})
export class SettingsComponent implements OnInit {
  settingsForm!: FormGroup;
  isLoading = true;
  isSaving = false;

  constructor(
    private fb: FormBuilder,
    private settingsService: SettingsService,
    private toastService: ToastService,
    private router: Router,
  ) {}

  ngOnInit(): void {
    this.settingsForm = this.fb.group({
      site_name: ['', Validators.required],
      support_email: ['', [Validators.required, Validators.email]],
      enable_welcome_emails: [true],
      enable_password_reset_emails: [true],
      default_access_level: ['user'],
      maintenance_mode: [false],
    });

    this.settingsService.getSettings().subscribe({
      next: (res) => {
        const s = res.settings;
        this.settingsForm.patchValue({
          site_name: s.site_name ?? 'CPD Portal',
          support_email: s.support_email ?? '',
          enable_welcome_emails: s.enable_welcome_emails === 'true',
          enable_password_reset_emails: s.enable_password_reset_emails === 'true',
          default_access_level: s.default_access_level ?? 'user',
          maintenance_mode: s.maintenance_mode === 'true',
        });
        this.isLoading = false;
      },
      error: () => {
        this.toastService.error('Failed to load settings.');
        this.isLoading = false;
      },
    });
  }

  onSave(): void {
    if (this.settingsForm.invalid) {
      this.settingsForm.markAllAsTouched();
      return;
    }

    this.isSaving = true;
    const formValues = this.settingsForm.value;

    // Convert booleans back to string format for the backend
    const payload: Partial<PlatformSettings> = {
      site_name: formValues.site_name,
      support_email: formValues.support_email,
      enable_welcome_emails: formValues.enable_welcome_emails ? 'true' : 'false',
      enable_password_reset_emails: formValues.enable_password_reset_emails ? 'true' : 'false',
      default_access_level: formValues.default_access_level,
      maintenance_mode: formValues.maintenance_mode ? 'true' : 'false',
    };

    this.settingsService.updateSettings(payload).subscribe({
      next: () => {
        this.isSaving = false;
        this.toastService.success('Settings saved successfully!');
      },
      error: (err) => {
        this.isSaving = false;
        const msg = err?.error?.message ?? 'Failed to save settings.';
        this.toastService.error(msg);
      },
    });
  }

  cancel(): void {
    this.router.navigate(['/admin/overview']);
  }
}
