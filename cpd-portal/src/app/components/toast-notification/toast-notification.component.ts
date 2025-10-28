import { Component, OnInit, OnDestroy, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-toast-notification',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './toast-notification.component.html',
  styleUrls: ['./toast-notification.component.css']
})
export class ToastNotificationComponent implements OnInit, OnDestroy {
  @Input() message: string = '';
  @Input() type: 'success' | 'error' | 'info' = 'info';
  @Input() duration: number = 3000; // milliseconds
  @Output() closed = new EventEmitter<void>();

  progress: number = 100;
  private timer: any;
  private progressInterval: any;

  ngOnInit(): void {
    const intervalTime = 50; // Update progress every 50ms
    const totalSteps = this.duration / intervalTime;
    const decrement = 100 / totalSteps;

    this.progressInterval = setInterval(() => {
      this.progress -= decrement;
      if (this.progress <= 0) {
        this.progress = 0;
        clearInterval(this.progressInterval);
      }
    }, intervalTime);

    this.timer = setTimeout(() => {
      this.close();
    }, this.duration);
  }

  ngOnDestroy(): void {
    if (this.timer) {
      clearTimeout(this.timer);
    }
    if (this.progressInterval) {
      clearInterval(this.progressInterval);
    }
  }

  close(): void {
    this.closed.emit();
  }

  get toastClass(): string {
    return `toast-${this.type}`;
  }
}