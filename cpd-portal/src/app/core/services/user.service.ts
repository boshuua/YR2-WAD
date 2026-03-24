import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable, defer, map, switchMap, tap } from 'rxjs';
import { environment } from '../../../environments/environment';
import { User } from '../models/user.model';
import { ApiResponse, PaginatedResponse } from '../models/api-response.model';
import { UserDashboard, Attachment, AssignCoursePayload } from '../models/dashboard.model';

@Injectable({
  providedIn: 'root',
})
export class UserService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) {}

  private ensureCsrfToken(): Observable<string> {
    return defer(() => {
      const existing = sessionStorage.getItem('csrfToken');
      if (existing && existing.trim().length > 0) {
        return defer(() => Promise.resolve(existing));
      }
      return this.http
        .post<{ csrfToken: string }>(`${this.apiUrl}/csrf.php`, {}, { withCredentials: true })
        .pipe(
          map((res) => res.csrfToken),
          tap((token) => sessionStorage.setItem('csrfToken', token)),
        );
    });
  }

  getUsers(page: number = 1, limit: number = 10): Observable<PaginatedResponse<User>> {
    return this.http.get<PaginatedResponse<User>>(
      `${this.apiUrl}/get_users.php?page=${page}&limit=${limit}`,
      { withCredentials: true }
    );
  }

  getUserById(userId: number): Observable<User> {
    return this.http
      .get<PaginatedResponse<User>>(`${this.apiUrl}/get_users.php?page=1&limit=1000`, {
        withCredentials: true,
      })
      .pipe(
        map((response) => {
          const user = response.data.find((u) => u.id === userId);
          if (!user) {
            throw new Error(`User with ID ${userId} not found`);
          }
          return user;
        }),
      );
  }

  getUserDashboard(userId: number): Observable<UserDashboard> {
    return this.http.get<UserDashboard>(`${this.apiUrl}/get_user_dashboard.php?id=${userId}`, {
      withCredentials: true,
    });
  }

  adminCreateUser(userData: Partial<User>): Observable<ApiResponse> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.post<ApiResponse>(`${this.apiUrl}/admin_create_user.php`, userData, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken }),
        }),
      ),
    );
  }

  adminUpdateUser(userId: number, userData: Partial<User>): Observable<ApiResponse> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.put<ApiResponse>(`${this.apiUrl}/admin_update_user.php?id=${userId}`, userData, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken }),
        }),
      ),
    );
  }

  adminDeleteUser(userId: number): Observable<ApiResponse> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.delete<ApiResponse>(`${this.apiUrl}/admin_delete_user.php?id=${userId}`, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken }),
        }),
      ),
    );
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

  updateSelf(userData: Partial<User>): Observable<ApiResponse> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.put<ApiResponse>(`${this.apiUrl}/update_self.php`, userData, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken }),
        }),
      ),
    );
  }

  updateSelfPassword(passwordData: {
    current_password: string;
    new_password: string;
  }): Observable<ApiResponse> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.put<ApiResponse>(`${this.apiUrl}/update_self_password.php`, passwordData, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken }),
        }),
      ),
    );
  }


  uploadUserAttachment(userId: number, file: File): Observable<ApiResponse> {
    const formData = new FormData();
    formData.append('user_id', userId.toString());
    formData.append('file', file);

    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.post<ApiResponse>(`${this.apiUrl}/upload_user_attachment.php`, formData, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken }),
        }),
      ),
    );
  }

  deleteUserAttachment(attachmentId: number): Observable<ApiResponse> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.delete<ApiResponse>(
          `${this.apiUrl}/delete_user_attachment.php?id=${attachmentId}`,
          {
            withCredentials: true,
            headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken }),
          },
        ),
      ),
    );
  }

  assignCourse(data: AssignCoursePayload): Observable<ApiResponse> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.post<ApiResponse>(`${this.apiUrl}/assign_course.php`, data, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken }),
        }),
      ),
    );
  }
}
