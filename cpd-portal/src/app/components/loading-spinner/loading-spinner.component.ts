import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { LoadingService } from '../../service/loading.service';

@Component({
  selector: 'app-loading-spinner',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="loading-overlay" *ngIf="loadingService.isLoading$ | async">
      <div class="spinner-container">
        <div class="spinner"></div>
        <div class="loading-text">Loading...</div>
      </div>
    </div>
  `,
  styles: [`
    .loading-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(255, 255, 255, 0.8);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 9999;
      backdrop-filter: blur(2px);
    }

    .spinner-container {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 15px;
    }

    .spinner {
      width: 50px;
      height: 50px;
      border: 4px solid #f3f3f3;
      border-top: 4px solid var(--primary-color, #68aedd);
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }

    .loading-text {
      font-family: 'Roboto', sans-serif;
      color: var(--sidebar-bg, #2c3e50);
      font-weight: 500;
      letter-spacing: 1px;
      text-transform: uppercase;
      font-size: 12px;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  `]
})
export class LoadingSpinnerComponent {
  constructor(public loadingService: LoadingService) {}
}
