import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';

@Injectable({
  providedIn: 'root'
})
export class BudgetService {
  private apiUrl = 'http://51.178.87.234 :8021/api';

  constructor(private http: HttpClient) {}

  // 🟢 Helpers
  private getActivePropId() {
    return localStorage.getItem('active_propriete_id') || '';
  }
  private getHeaders() {
    return new HttpHeaders({ 'residence_id': this.getActivePropId() });
  }

  chargerDonnees(payload: any) {
    payload.residence_id = this.getActivePropId(); // 🟢
    return this.http.post(`${this.apiUrl}/budgets/charger`, payload, { headers: this.getHeaders(), withCredentials: true });
  }

  ajouterEncaissement(payload: any) {
    payload.residence_id = this.getActivePropId(); // 🟢
    return this.http.post(`${this.apiUrl}/encaissements/ajouter`, payload, { headers: this.getHeaders(), withCredentials: true });
  }

  ajouterDepense(payload: any) {
    payload.residence_id = this.getActivePropId(); // 🟢
    return this.http.post(`${this.apiUrl}/depenses/ajouter`, payload, { headers: this.getHeaders(), withCredentials: true });
  }

  telechargerReleve(payload: any) {
    payload.residence_id = this.getActivePropId(); // 🟢
    return this.http.post(`${this.apiUrl}/budgets/releve`, payload, { 
      headers: this.getHeaders(), 
      withCredentials: true,
      responseType: 'blob' 
    });
  }
}