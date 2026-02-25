import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable, BehaviorSubject } from 'rxjs';
import { tap } from 'rxjs/operators';
import { environment } from '../../../environments/environment';

export interface PlatformSettings {
  site_name: string;
  support_email: string;
  enable_welcome_emails: string;
  enable_password_reset_emails: string;
  default_access_level: string;
  maintenance_mode: string;
}

interface GetSettingsResponse {
  settings: PlatformSettings;
}

@Injectable({
  providedIn: 'root',
})
export class SettingsService {
  private apiUrl = environment.apiUrl;

  private _maintenanceMode = new BehaviorSubject<boolean>(false);
  maintenanceMode$ = this._maintenanceMode.asObservable();

  constructor(private http: HttpClient) {}

  getSettings(): Observable<GetSettingsResponse> {
    return this.http
      .get<GetSettingsResponse>(`${this.apiUrl}/get_settings.php`, {
        withCredentials: true,
      })
      .pipe(
        tap((res) => {
          if (res?.settings?.maintenance_mode === 'true') {
            this._maintenanceMode.next(true);
          } else {
            this._maintenanceMode.next(false);
          }
        }),
      );
  }

  updateSettings(settings: Partial<PlatformSettings>): Observable<{ message: string }> {
    const csrfToken = sessionStorage.getItem('csrfToken') || '';
    return this.http.post<{ message: string }>(
      `${this.apiUrl}/admin_update_settings.php`,
      settings,
      {
        withCredentials: true,
        headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken }),
      },
    );
  }
}
