import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable, defer, map, switchMap, tap } from 'rxjs';
import { environment } from '../../environments/environment';

type AccessLevel = 'admin' | 'user';

export type MeResponse = {
  user: {
    user_id: number | null;
    email: string | null;
    first_name: string | null;
    last_name: string | null;
    access_level: AccessLevel | null;
  };
};

export type CsrfResponse = { csrfToken: string };

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) { }

  loginUser(credentials: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/user_login.php`, credentials, { withCredentials: true });
  }

  getMe(): Observable<MeResponse> {
    return this.http.get<MeResponse>(`${this.apiUrl}/me.php`, { withCredentials: true });
  }

  getCsrfToken(): Observable<CsrfResponse> {
    // POST works with servers that enforce POST-only CSRF bootstrap
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
          // Persist for subsequent requests in this tab
          sessionStorage.setItem('csrfToken', token);
        })
      );
    });
  }

  adminCreateUser(userData: any): Observable<any> {
    // (This endpoint is POST too, so it needs CSRF.)
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.post(`${this.apiUrl}/admin_create_user.php`, userData, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken })
        })
      )
    );
  }

  // Renamed from getAllUsers - Fetches all users
  getUsers(): Observable<any> {
    return this.http.get(`${this.apiUrl}/get_users.php`, { withCredentials: true });
  }

  getUserById(userId: number): Observable<any> {
    return this.http.get(`${this.apiUrl}/get_users.php?id=${userId}`, { withCredentials: true });
  }

  getUserDashboard(userId: number): Observable<any> {
    return this.http.get(`${this.apiUrl}/get_user_dashboard.php?id=${userId}`, { withCredentials: true });
  }

  uploadUserAttachment(userId: number, file: File): Observable<any> {
    const formData = new FormData();
    formData.append('user_id', userId.toString());
    formData.append('file', file);

    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.post(`${this.apiUrl}/upload_user_attachment.php`, formData, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken })
        })
      )
    );
  }

  deleteUserAttachment(attachmentId: number): Observable<any> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.delete(`${this.apiUrl}/delete_user_attachment.php?id=${attachmentId}`, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken })
        })
      )
    );
  }

  adminUpdateUser(userId: number, userData: any): Observable<any> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.put(`${this.apiUrl}/admin_update_user.php?id=${userId}`, userData, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken })
        })
      )
    );
  }

  adminDeleteUser(userId: number): Observable<any> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.delete(`${this.apiUrl}/admin_delete_user.php?id=${userId}`, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken })
        })
      )
    );
  }

  adminCreateCourse(courseData: any): Observable<any> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.post(`${this.apiUrl}/admin_create_course.php`, courseData, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken })
        })
      )
    );
  }

  adminCreateCourseFromTemplate(templateData: any): Observable<any> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.post(`${this.apiUrl}/admin_create_course_from_template.php`, templateData, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken })
        })
      )
    );
  }

  getCourses(type: 'all' | 'active' | 'template' = 'all'): Observable<any> {
    return this.http.get(`${this.apiUrl}/get_courses.php?type=${type}`, { withCredentials: true });
  }

  adminUpdateCourse(courseId: number, courseData: any): Observable<any> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.put(`${this.apiUrl}/admin_update_course.php?id=${courseId}`, courseData, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken })
        })
      )
    );
  }

  adminDeleteCourse(courseId: number): Observable<any> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.delete(`${this.apiUrl}/admin_delete_course.php?id=${courseId}`, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken })
        })
      )
    );
  }

  getCourseById(courseId: number): Observable<any> {
    return this.http.get(`${this.apiUrl}/get_course_by_id.php?id=${courseId}`, { withCredentials: true });
  }

  getCourseLessons(courseId: number): Observable<any> {
    return this.http.get(`${this.apiUrl}/get_course_lessons.php?course_id=${courseId}`, { withCredentials: true });
  }

  adminUpdatePassword(passwordData: any): Observable<any> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.put(`${this.apiUrl}/admin_update_password.php`, passwordData, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken })
        })
      )
    );
  }

  getUserCourses(): Observable<any> {
    return this.http.get(`${this.apiUrl}/get_user_courses.php`, { withCredentials: true });
  }

  updateCourseProgress(progressData: any): Observable<any> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.post(`${this.apiUrl}/update_course_progress.php`, progressData, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken })
        })
      )
    );
  }

  getActivityLog(limit: number = 20): Observable<any> {
    return this.http.get(`${this.apiUrl}/get_activity_log.php?limit=${limit}`, { withCredentials: true });
  }

  // Question Management
  getCourseQuestions(courseId: number): Observable<any> {
    return this.http.get(`${this.apiUrl}/get_course_questions.php?course_id=${courseId}`, { withCredentials: true });
  }

  adminCreateQuestion(questionData: any): Observable<any> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.post(`${this.apiUrl}/admin_create_question.php`, questionData, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken })
        })
      )
    );
  }

  adminDeleteQuestion(questionId: number): Observable<any> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.delete(`${this.apiUrl}/admin_delete_question.php?id=${questionId}`, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken })
        })
      )
    );
  }

  // Course Enrollment
  enrollCourse(courseId: number): Observable<any> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.post(
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

  // Quiz Submission
  submitQuiz(courseId: number, score: number): Observable<any> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.post(
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
}
