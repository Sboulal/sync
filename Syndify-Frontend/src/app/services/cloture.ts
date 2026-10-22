import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class ClotureService {
  private apiUrl = 'http://51.178.87.234 :8021/api/clotures'; 

  constructor(private http: HttpClient) {}

  private getActivePropId() {
    return localStorage.getItem('active_propriete_id') || '';
  }

  private getHeaders() {
    return new HttpHeaders({ 
      'residence_id': this.getActivePropId(),
      'propriete_id': this.getActivePropId() 
    });
  }

  getClotureData(sp_id: string, se_id: string): Observable<any> {
    const propId = this.getActivePropId() || sp_id;
    return this.http.post(`${this.apiUrl}/charger`, { 
      se_identifier: se_id,
      residence_id: propId,
      propriete_id: propId // 🟢 Zidnaha hna
    }, { headers: this.getHeaders(), withCredentials: true });
  }

  enregistrerBrouillon(payload: any): Observable<any> {
    payload.residence_id = this.getActivePropId();
    payload.propriete_id = this.getActivePropId(); // 🟢 Zidnaha hna
    return this.http.post(`${this.apiUrl}/enregistrer`, payload, { headers: this.getHeaders(), withCredentials: true });
  }

  finaliserCloture(payload: any): Observable<any> {
    payload.residence_id = this.getActivePropId();
    payload.propriete_id = this.getActivePropId(); // 🟢 Zidnaha hna
    return this.http.post(`${this.apiUrl}/finaliser`, payload, { headers: this.getHeaders(), withCredentials: true });
  }
}