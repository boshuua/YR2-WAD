import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable, defer, map, switchMap, tap } from 'rxjs';
import { environment } from '../../../environments/environment';
import { User } from '../models/user.model';
import { Course } from '../models/course.model';
import { ApiResponse, AuthResponse, CsrfResponse } from '../models/api-response.model';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) { }

  loginUser(credentials: { email: string, password: string }): Observable<AuthResponse> {
    return this.http.post<AuthResponse>(`${this.apiUrl}/user_login.php`, credentials, { withCredentials: true });
  }

  getMe(): Observable<{ user: User }> {
    return this.http.get<{ user: User }>(`${this.apiUrl}/me.php`, { withCredentials: true });
  }

  getCsrfToken(): Observable<CsrfResponse> {
    return this.http.post<CsrfResponse>(`${this.apiUrl}/csrf.php`, {}, { withCredentials: true });
  }

  private ensureCsrfToken(): Observable<string> {
    return defer(() => {
      const existing = sessionStorage.getItem('csrfToken');
      if (existing && existing.trim().length > 0) {
        return defer(() => Promise.resolve(existing));
      }

      return this.getCsrfToken().pipe(
        map((res) => res.csrfToken),
        tap((token) => {
          sessionStorage.setItem('csrfToken', token);
        })
      );
    });
  }

  adminCreateUser(userData: Partial<User>): Observable<ApiResponse> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.post<ApiResponse>(`${this.apiUrl}/admin_create_user.php`, userData, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken })
        })
      )
    );
  }

  getUsers(): Observable<User[]> {
    return this.http.get<User[]>(`${this.apiUrl}/get_users.php`, { withCredentials: true });
  }

  getUserById(userId: number): Observable<User> {
    return this.http.get<User>(`${this.apiUrl}/get_users.php?id=${userId}`, { withCredentials: true });
  }

  getUserDashboard(userId: number): Observable<any> { // TODO: Define Dashboard Response
    return this.http.get(`${this.apiUrl}/get_user_dashboard.php?id=${userId}`, { withCredentials: true });
  }

  uploadUserAttachment(userId: number, file: File): Observable<ApiResponse> {
    const formData = new FormData();
    formData.append('user_id', userId.toString());
    formData.append('file', file);

    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.post<ApiResponse>(`${this.apiUrl}/upload_user_attachment.php`, formData, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken })
        })
      )
    );
  }

  deleteUserAttachment(attachmentId: number): Observable<ApiResponse> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.delete<ApiResponse>(`${this.apiUrl}/delete_user_attachment.php?id=${attachmentId}`, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken })
        })
      )
    );
  }

  adminUpdateUser(userId: number, userData: Partial<User>): Observable<ApiResponse> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.put<ApiResponse>(`${this.apiUrl}/admin_update_user.php?id=${userId}`, userData, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken })
        })
      )
    );
  }

  adminDeleteUser(userId: number): Observable<ApiResponse> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.delete<ApiResponse>(`${this.apiUrl}/admin_delete_user.php?id=${userId}`, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken })
        })
      )
    );
  }

  adminCreateCourse(courseData: Partial<Course>): Observable<ApiResponse> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.post<ApiResponse>(`${this.apiUrl}/admin_create_course.php`, courseData, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken })
        })
      )
    );
  }

  adminCreateCourseFromTemplate(templateData: { template_id: number, start_date: string, attendees?: number[], course_id?: number }): Observable<ApiResponse> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.post<ApiResponse>(`${this.apiUrl}/admin_create_course_from_template.php`, templateData, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken })
        })
      )
    );
  }

  getCourses(type: 'all' | 'active' | 'template' | 'locked' | 'upcoming' = 'all'): Observable<Course[]> {
    return this.http.get<Course[]>(`${this.apiUrl}/get_courses.php?type=${type}`, { withCredentials: true });
  }

  getUpcomingCourses(): Observable<Course[]> {
    return this.getCourses('upcoming');
  }

  adminUpdateCourse(courseId: number, courseData: Partial<Course>): Observable<ApiResponse> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.put<ApiResponse>(`${this.apiUrl}/admin_update_course.php?id=${courseId}`, courseData, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken })
        })
      )
    );
  }

  adminDeleteCourse(courseId: number): Observable<ApiResponse> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.delete<ApiResponse>(`${this.apiUrl}/admin_delete_course.php?id=${courseId}`, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken })
        })
      )
    );
  }

  getCourseById(courseId: number): Observable<Course> {
    return this.http.get<Course>(`${this.apiUrl}/get_course_by_id.php?id=${courseId}`, { withCredentials: true });
  }

  getCourseLessons(courseId: number): Observable<any> { // TODO: Lesson Model
    return this.http.get(`${this.apiUrl}/get_course_lessons.php?course_id=${courseId}`, { withCredentials: true });
  }

  adminUpdatePassword(passwordData: { user_id?: number, current_password?: string, new_password: string }): Observable<ApiResponse> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.put<ApiResponse>(`${this.apiUrl}/admin_update_password.php`, passwordData, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken })
        })
      )
    );
  }

  getUserCourses(): Observable<any[]> { // TODO: UserCourse Model
    return this.http.get<any[]>(`${this.apiUrl}/get_user_courses.php`, { withCredentials: true });
  }

  updateCourseProgress(progressData: { course_id: number, status?: string }): Observable<ApiResponse> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.post<ApiResponse>(`${this.apiUrl}/update_course_progress.php`, progressData, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken })
        })
      )
    );
  }

  getActivityLog(limit: number = 20): Observable<any[]> { // TODO: Activity Model
    return this.http.get<any[]>(`${this.apiUrl}/get_activity_log.php?limit=${limit}`, { withCredentials: true });
  }

  getCourseQuestions(courseId: number): Observable<any[]> { // TODO: Question Model
    return this.http.get<any[]>(`${this.apiUrl}/get_course_questions.php?course_id=${courseId}`, { withCredentials: true });
  }

  adminCreateQuestion(questionData: any): Observable<ApiResponse> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.post<ApiResponse>(`${this.apiUrl}/admin_create_question.php`, questionData, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken })
        })
      )
    );
  }

  adminDeleteQuestion(questionId: number): Observable<ApiResponse> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.delete<ApiResponse>(`${this.apiUrl}/admin_delete_question.php?id=${questionId}`, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken })
        })
      )
    );
  }

  enrollCourse(courseId: number): Observable<ApiResponse> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.post<ApiResponse>(
          `${this.apiUrl}/enroll_course.php`,
          { course_id: courseId },
          {
            withCredentials: true,
            headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken })
          }
        )
      )
    );
  }

  submitQuiz(courseId: number, score: number): Observable<ApiResponse> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.post<ApiResponse>(
          `${this.apiUrl}/submit_quiz.php`,
          { course_id: courseId, score: score },
          {
            withCredentials: true,
            headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken })
          }
        )
      )
    );
  }

  assignCourse(data: { user_id: number, course_id: number }): Observable<ApiResponse> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.post<ApiResponse>(`${this.apiUrl}/assign_course.php`, data, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken })
        })
      )
    );
  }
}
