import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule, DatePipe } from '@angular/common';
import { CourseService } from '../../../core/services/course.service';
import { LoadingService } from '../../../core/services/loading.service';
import { UserCourse } from '../../../core/models/dashboard.model';
import { Subscription } from 'rxjs';

interface CalendarDay {
  date: Date;
  isCurrentMonth: boolean;
  isToday: boolean;
}

interface CourseSegment {
  id: number;
  title: string;
  gridRow: number;
  gridColumnStart: number;
  gridColumnEnd: number;
  lane: number;
  status: string;
}

@Component({
  selector: 'app-user-calendar',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './user-calendar.component.html',
  styleUrls: ['./user-calendar.component.css'],
  providers: [DatePipe],
})
export class UserCalendarComponent implements OnInit {
  Math = Math;
  currentDate = new Date();
  daysInMonth: CalendarDay[] = [];
  courseSegments: CourseSegment[] = [];
  weekDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
  
  userCourses: UserCourse[] = [];

  constructor(
    private courseService: CourseService,
    private loadingService: LoadingService
  ) {}

  ngOnInit(): void {
    this.generateCalendar();
    this.loadUserCourses();
  }

  loadUserCourses(): void {
    this.loadingService.show();
    this.courseService.getUserCourses().subscribe({
      next: (data) => {
        this.userCourses = data;
        this.mapCoursesToCalendar();
        this.loadingService.hide();
      },
      error: (err) => {
        console.error('Failed to load user courses', err);
        this.loadingService.hide();
      }
    });
  }

  generateCalendar(): void {
    const year = this.currentDate.getFullYear();
    const month = this.currentDate.getMonth();

    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);

    let startDayOfWeek = firstDay.getDay() - 1;
    if (startDayOfWeek === -1) startDayOfWeek = 6;

    const days: CalendarDay[] = [];

    // Prev month padding
    for (let i = startDayOfWeek; i > 0; i--) {
      days.push({
        date: new Date(year, month, 1 - i),
        isCurrentMonth: false,
        isToday: false
      });
    }

    // Current month days
    for (let i = 1; i <= lastDay.getDate(); i++) {
      const date = new Date(year, month, i);
      days.push({
        date,
        isCurrentMonth: true,
        isToday: this.isSameDate(date, new Date())
      });
    }

    // Next month padding
    const totalCells = 42;
    const remaining = totalCells - days.length;
    for (let i = 1; i <= remaining; i++) {
      days.push({
        date: new Date(year, month + 1, i),
        isCurrentMonth: false,
        isToday: false
      });
    }

    this.daysInMonth = days;
    this.mapCoursesToCalendar();
  }

  prevMonth(): void {
    this.currentDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() - 1, 1);
    this.generateCalendar();
  }

  nextMonth(): void {
    this.currentDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() + 1, 1);
    this.generateCalendar();
  }

  today(): void {
    this.currentDate = new Date();
    this.generateCalendar();
  }

  mapCoursesToCalendar(): void {
    if (!this.daysInMonth.length || !this.userCourses.length) return;

    this.courseSegments = [];
    
    // We'll track which lanes are occupied in each row/col
    const rowLanes: boolean[][][] = Array.from({ length: 7 }, () => 
      Array.from({ length: 8 }, () => []) 
    );

    this.userCourses.forEach(course => {
      // Use start_date/end_date if available, fallback to enrolled_at
      const startDateStr = course.start_date || course.enrolled_at;
      const endDateStr = course.end_date || startDateStr;

      if (!startDateStr) return;

      const start = new Date(startDateStr);
      start.setHours(0, 0, 0, 0);
      const end = new Date(endDateStr || startDateStr);
      end.setHours(23, 59, 59, 999);

      // Find indices in daysInMonth
      const indices = this.daysInMonth
        .map((day, idx) => ({ day, idx }))
        .filter(item => item.day.date >= start && item.day.date <= end)
        .map(item => item.idx);

      if (indices.length === 0) return;

      // Group into continuous segments per row
      let currentSegment: { row: number, startCol: number, endCol: number } | null = null;
      const segments: { row: number, startCol: number, endCol: number }[] = [];

      indices.forEach(idx => {
        const row = Math.floor(idx / 7) + 1;
        const col = (idx % 7) + 1;

        if (!currentSegment || currentSegment.row !== row || currentSegment.endCol !== col - 1) {
          if (currentSegment) segments.push(currentSegment);
          currentSegment = { row, startCol: col, endCol: col };
        } else {
          currentSegment.endCol = col;
        }
      });
      if (currentSegment) segments.push(currentSegment);

      // Assign lane
      let lane = 0;
      let laneFound = false;
      while (!laneFound) {
        let conflict = false;
        segments.forEach(seg => {
          for (let c = seg.startCol; c <= seg.endCol; c++) {
            if (rowLanes[seg.row] && rowLanes[seg.row][c] && rowLanes[seg.row][c][lane]) {
              conflict = true;
              break;
            }
          }
          if (conflict) return;
        });

        if (!conflict) {
          laneFound = true;
        } else {
          lane++;
        }
      }

      // Mark lanes as occupied and add segments
      segments.forEach(seg => {
        for (let c = seg.startCol; c <= seg.endCol; c++) {
          if (!rowLanes[seg.row]) rowLanes[seg.row] = [];
          if (!rowLanes[seg.row][c]) rowLanes[seg.row][c] = [];
          rowLanes[seg.row][c][lane] = true;
        }

        this.courseSegments.push({
          id: course.id,
          title: course.title,
          gridRow: seg.row,
          gridColumnStart: seg.startCol,
          gridColumnEnd: seg.endCol + 1,
          lane: lane,
          status: course.user_progress_status
        });
      });
    });
  }

  private isSameDate(d1: Date, d2: Date): boolean {
    return (
      d1.getDate() === d2.getDate() &&
      d1.getMonth() === d2.getMonth() &&
      d1.getFullYear() === d2.getFullYear()
    );
  }
}
