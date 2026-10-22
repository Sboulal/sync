import { Injectable } from "@angular/core";
import { switchMap, tap } from "rxjs/operators";
import { HttpRequest, HttpHandler, HttpEvent, HttpInterceptor, HttpResponse } from "@angular/common/http";
import { from, Observable } from 'rxjs';
import { StorageService } from "./storage.service";
import { IdpReqService } from "idp-com-sdk";

@Injectable({
  providedIn: 'root'
})
export class HttpInterceptorService implements HttpInterceptor {

  constructor(
    private idpreqservice: IdpReqService,
    private storageservice: StorageService
  ) { }

  intercept(req: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    return from((this.idpreqservice.prepareHeaders(this.storageservice.getItem('access_token') || this.storageservice.getItem('public_token') || null, req, 'fr'))).pipe(
      switchMap((resolvedRequest) => {
        return next.handle(resolvedRequest).pipe(
          tap({
            next: (event) => {
              if (event instanceof HttpResponse) {
                if (event.body['refreshed_token']) this.storageservice.setItem('access_token', event.body['refreshed_token']);
              }
            },
            error: (error) => {
              if(error?.error?.force_logout) {
                 // LOG OUT YOUR APP HERE;
              }
              if (error?.error?.refreshed_token) {
                 this.storageservice.setItem('access_token', error.error.refreshed_token);
              }
            }
          })
        );
      })
    );
  }
}