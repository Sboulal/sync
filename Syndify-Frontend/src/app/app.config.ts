import { ApplicationConfig, provideZoneChangeDetection, LOCALE_ID, APP_INITIALIZER } from '@angular/core';
import { provideRouter, withInMemoryScrolling } from '@angular/router';

// Zedt HttpClient w HTTP_INTERCEPTORS w withInterceptorsFromDi
import { provideHttpClient, withInterceptorsFromDi, HTTP_INTERCEPTORS, HttpClient } from '@angular/common/http';

import { registerLocaleData } from '@angular/common';
import localeFr from '@angular/common/locales/fr';

import { routes } from './app.routes';
// 🟢 7iydna l'import dyal authInterceptor mn hna
import { IdpSecurityService, IdpReqService } from 'idp-com-sdk';
import { HttpInterceptorService } from './services/http-interceptor.service';

registerLocaleData(localeFr, 'fr');

export const appConfig: ApplicationConfig = {
  providers: [
    provideRouter(
      routes,
      withInMemoryScrolling({ scrollPositionRestoration: 'top' })
    ),
    { provide: LOCALE_ID, useValue: 'fr' },

    // --- Configuration jdida dyal HttpClient ---
    provideHttpClient(
      // 🟢 7iydna l'interceptor l9dim bach ma-ydirch conflit m3a l'SDK
      withInterceptorsFromDi()             
    ),
    
    // Ajout dyal l'Interceptor jdid li saybna (HttpInterceptorService)
    {
      provide: HTTP_INTERCEPTORS,
      useClass: HttpInterceptorService,
      multi: true
    },

    // --- Configuration dyal IDP SDK ---
    IdpSecurityService,
    {
      provide: APP_INITIALIZER,
      useFactory: (idpSecService: IdpSecurityService) => () => idpSecService.initWasm(),
      deps: [IdpSecurityService],
      multi: true
    },
   {
      provide: APP_INITIALIZER,
      useFactory: (idpReqService: IdpReqService, http: HttpClient) => () => idpReqService.boot(
        "019f4beb-1932-704d-81c8-3ab3bebd68e3", 
        http,
        'https://sbe-identify.nomade-cloud.com/auth/authorize', 
        'https://api.sbe-identify.nomade-cloud.com'             
      ),
      deps: [IdpReqService, HttpClient],
      multi: true
    }
  ]
};