import { inject, Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';

@Injectable({
  providedIn: 'root'
})
export class ExerciceService {
  private http = inject(HttpClient);
  private apiUrl = 'http://51.178.87.234:8021/api/exercices';

  // 🟢 Helpers
  private getActivePropId() {
    return localStorage.getItem('active_propriete_id') || '';
  }
  private getHeaders() {
    return new HttpHeaders({ 'residence_id': this.getActivePropId() });
  }

  getListe() {
    return this.http.post(`${this.apiUrl}/liste`, { 
      residence_id: this.getActivePropId() 
    }, { headers: this.getHeaders(), withCredentials: true });
  }

  ajouter(data: any) {
    const payload = {
      ...data,
      period: data.periode,
      residence_id: this.getActivePropId() // 🟢
    };
    return this.http.post(`${this.apiUrl}/ajouter`, payload, { headers: this.getHeaders(), withCredentials: true });
  }

  modifier(data: any) {
    const payload = {
      ...data,
      se_identifier: data.se_identifier,
      period: data.periode || data.period,
      residence_id: this.getActivePropId() // 🟢
    };
    return this.http.post(`${this.apiUrl}/modifier`, payload, { headers: this.getHeaders(), withCredentials: true });
  }

  demarrer(se_identifier: string) {
    return this.http.post(`${this.apiUrl}/demarrer`, { 
      se_identifier,
      residence_id: this.getActivePropId() // 🟢
    }, { headers: this.getHeaders(), withCredentials: true });
  }

  supprimer(se_id: string) {
    return this.http.post(`${this.apiUrl}/supprimer`, { 
      se_identifier: se_id,
      residence_id: this.getActivePropId() // 🟢
    }, { headers: this.getHeaders(), withCredentials: true });
  }
}