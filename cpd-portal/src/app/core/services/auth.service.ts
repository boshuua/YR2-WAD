import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable, defer, map, switchMap, tap } from 'rxjs';
import { environment } from '../../../environments/environment';
import { User } from '../models/user.model';
import { ApiResponse, AuthResponse, CsrfResponse } from '../models/api-response.model';

@Injectable({
  providedIn: 'root',
})
export class AuthService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) {}

  loginUser(credentials: { email: string; password: string }): Observable<AuthResponse> {
    return this.http.post<AuthResponse>(`${this.apiUrl}/user_login.php`, credentials, {
      withCredentials: true,
    });
  }

  forgotPassword(email: string): Observable<ApiResponse> {
    return this.http.post<ApiResponse>(
      `${this.apiUrl}/forgot_password.php`,
      { email },
      {
        withCredentials: true,
      },
    );
  }

  getMe(): Observable<{ user: User }> {
    return this.http.get<{ user: User }>(`${this.apiUrl}/me.php`, { withCredentials: true });
  }

  getCsrfToken(): Observable<CsrfResponse> {
    return this.http.post<CsrfResponse>(`${this.apiUrl}/csrf.php`, {}, { withCredentials: true });
  }

  ensureCsrfToken(): Observable<string> {
    return defer(() => {
      const existing = sessionStorage.getItem('csrfToken');
      if (existing && existing.trim().length > 0) {
        return defer(() => Promise.resolve(existing));
      }
      return this.getCsrfToken().pipe(
        map((res) => res.csrfToken),
        tap((token) => sessionStorage.setItem('csrfToken', token)),
      );
    });
  }

  adminUpdatePassword(passwordData: {
    user_id?: number;
    current_password?: string;
    new_password: string;
  }): Observable<ApiResponse> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.put<ApiResponse>(`${this.apiUrl}/admin_update_password.php`, passwordData, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken }),
        }),
      ),
    );
  }
}
