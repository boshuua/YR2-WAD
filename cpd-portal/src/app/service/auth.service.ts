import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private apiUrl = 'http://localhost:8000/api'; // Make sure this URL is correct

  constructor(private http: HttpClient) { }

  loginUser(credentials: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/user_login.php`, credentials, { withCredentials: true });
  }

  adminCreateUser(userData: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/admin_create_user.php`, userData, { withCredentials: true });
  }

  // Renamed from getAllUsers - Fetches all users
  getUsers(): Observable<any> {
    // Calls the script without an ID parameter
    return this.http.get(`${this.apiUrl}/get_users.php`, { withCredentials: true });
  }

  // Fetches a single user by ID
  getUserById(userId: number): Observable<any> {
    // Calls the *same* script but adds the ID query parameter
    return this.http.get(`${this.apiUrl}/get_users.php?id=${userId}`, { withCredentials: true });
  }

  adminUpdateUser(userId: number, userData: any): Observable<any> {
    return this.http.put(`${this.apiUrl}/admin_update_user.php?id=${userId}`, userData, {
      withCredentials: true
    });
  }

  adminDeleteUser(userId: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/admin_delete_user.php?id=${userId}`, {
      withCredentials: true
    });
  }

  adminCreateCourse(courseData: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/admin_create_course.php`, courseData, { withCredentials: true });
  }

  getCourses(): Observable<any> {
    return this.http.get(`${this.apiUrl}/get_courses.php`, { withCredentials: true });
  }

  adminUpdateCourse(courseId: number, courseData: any): Observable<any> {
    return this.http.put(`${this.apiUrl}/admin_update_course.php?id=${courseId}`, courseData, {
      withCredentials: true
    });
  }

  adminDeleteCourse(courseId: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/admin_delete_course.php?id=${courseId}`, {
      withCredentials: true
    });
  }

  getCourseById(courseId: number): Observable<any> {
    return this.http.get(`${this.apiUrl}/get_course_by_id.php?id=${courseId}`, { withCredentials: true });
  }

  adminUpdatePassword(passwordData: any): Observable<any> {
    return this.http.put(`${this.apiUrl}/admin_update_password.php`, passwordData, { withCredentials: true });
  }

  adminCreateLesson(lessonData: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/admin_create_lesson.php`, lessonData, { withCredentials: true });
  }

  adminUpdateLesson(lessonId: number, lessonData: any): Observable<any> {
    return this.http.put(`${this.apiUrl}/admin_update_lesson.php?id=${lessonId}`, lessonData, { withCredentials: true });
  }

  adminDeleteLesson(lessonId: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/admin_delete_lesson.php?id=${lessonId}`, { withCredentials: true });
  }

  getLessonsByCourse(courseId: number): Observable<any> {
    return this.http.get(`${this.apiUrl}/get_lessons_by_course.php?course_id=${courseId}`, { withCredentials: true });
  }

  getLessonById(courseId: number, lessonId: number): Observable<any> {
    return this.http.get(`${this.apiUrl}/get_lessons_by_course.php?course_id=${courseId}&id=${lessonId}`, { withCredentials: true });
  }

  adminCreateQuestion(questionData: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/admin_create_question.php`, questionData, { withCredentials: true });
  }

  adminUpdateQuestion(questionId: number, questionData: any): Observable<any> {
    return this.http.put(`${this.apiUrl}/admin_update_question.php?id=${questionId}`, questionData, { withCredentials: true });
  }

  adminDeleteQuestion(questionId: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/admin_delete_question.php?id=${questionId}`, { withCredentials: true });
  }

  getQuestionsByLesson(lessonId: number): Observable<any> {
    return this.http.get(`${this.apiUrl}/get_questions_by_lesson.php?lesson_id=${lessonId}`, { withCredentials: true });
  }

  getQuestionById(lessonId: number, questionId: number): Observable<any> {
    return this.http.get(`${this.apiUrl}/get_questions_by_lesson.php?lesson_id=${lessonId}&id=${questionId}`, { withCredentials: true });
  }

  getActivityLog(limit: number = 20): Observable<any> { // Default limit
    return this.http.get(`${this.apiUrl}/get_activity_log.php?limit=${limit}`, { withCredentials: true });
  }
}