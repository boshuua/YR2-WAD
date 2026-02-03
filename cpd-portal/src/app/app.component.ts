import { Component } from '@angular/core';
import { Router, RouterOutlet, NavigationStart, NavigationEnd, NavigationCancel, NavigationError, Event } from '@angular/router';
import { LoadingSpinnerComponent } from './components/loading-spinner/loading-spinner.component';
import { LoadingService } from './service/loading.service';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterOutlet, LoadingSpinnerComponent, CommonModule],
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.css']
})
export class AppComponent {
  title = 'cpd-portal';

  constructor(private router: Router, private loadingService: LoadingService) {
    this.router.events.subscribe((event: Event) => {
      if (event instanceof NavigationStart) {
        // Only show loader if we are NOT navigating to login OR the root path (which redirects to login)
        if (!event.url.includes('/login') && event.url !== '/') {
          this.loadingService.show();
        }
      } else if (
        event instanceof NavigationEnd ||
        event instanceof NavigationCancel ||
        event instanceof NavigationError
      ) {
        // Extend the visibility of the loading spinner to ensure a smoother transition
        setTimeout(() => {
          this.loadingService.hide();
        }, 500);
      }
    });
  }
}
