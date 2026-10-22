// FICHIER: src/app/core/services/page-title.service.ts
import { Injectable } from '@angular/core';
import { Title } from '@angular/platform-browser';
import { Router, NavigationEnd } from '@angular/router';
import { filter } from 'rxjs/operators';

@Injectable({ providedIn: 'root' })
export class PageTitleService {
  private publicTitle = 'La solution complète pour une gestion simplifiée de vos immeubles.';
  private appTitle = 'Syndify';

  constructor(private title: Title, private router: Router) {}

  init() {
    this.router.events
      .pipe(filter(e => e instanceof NavigationEnd))
      .subscribe((e: NavigationEnd) => {
        const isPublic = this.isPublicRoute(e.urlAfterRedirects);
        this.title.setTitle(isPublic ? this.publicTitle : this.appTitle);
      });
  }

  private isPublicRoute(url: string): boolean {
    const publicRoutes = ['/', '/login', '/register', '/contact', '/politique-protection-donnees'];
    return publicRoutes.some(r => url === r || url.startsWith(r + '?'));
  }
}