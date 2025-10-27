import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, NavigationEnd, Router, RouterModule, Data } from '@angular/router';
import { filter, map, startWith } from 'rxjs/operators';
import { Observable } from 'rxjs';

@Component({
  selector: 'app-breadcrumb',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './breadcrumb.component.html',
  styleUrls: ['./breadcrumb.component.css']
})
export class BreadcrumbComponent {
  public breadcrumbs$: Observable<any[]>;

  constructor(private router: Router, private activatedRoute: ActivatedRoute) {
    this.breadcrumbs$ = this.router.events.pipe(
      filter(event => event instanceof NavigationEnd),
      startWith(null), // Emit immediately on load
      map(() => this.createBreadcrumbs(this.activatedRoute.root))
    );
  }

  private createBreadcrumbs(route: ActivatedRoute, url: string = '', breadcrumbs: any[] = []): any[] {
    const children: ActivatedRoute[] = route.children;
    if (children.length === 0) {
      return breadcrumbs;
    }

    for (const child of children) {
      const routeURL: string = child.snapshot.url.map(segment => segment.path).join('/');
      let newUrl = url;
      if (routeURL !== '') {
        newUrl += `/${routeURL}`;
      }

      const data = child.snapshot.data;
      if (data.hasOwnProperty('breadcrumb')) {
        breadcrumbs.push({
          label: data['breadcrumb'],
          url: newUrl
        });
      }
      return this.createBreadcrumbs(child, newUrl, breadcrumbs);
    }
    return breadcrumbs;
  }
}