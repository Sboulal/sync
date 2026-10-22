import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root',
})
export class CleRepartitionService {
  
  // 🟢 IP Unifiée b7al l-Services l-khrin
  private baseUrl = 'http://51.178.87.234 :8021/api';
  private apiUrl = `${this.baseUrl}/cles-repartition`;

  constructor(private http: HttpClient) {}

  // 🟢 Helper bash njebdou l-ID dyal l-Résidence mn LocalStorage
  private getActivePropId() {
    return localStorage.getItem('active_propriete_id') || '';
  }

  // 🟢 Helper bash nsifto l-Headers dima
  private getHeaders() {
    return new HttpHeaders({
      'residence_id': this.getActivePropId()
    });
  }

  // ==========================================
  // 1. Récupérer la liste des clés de répartition
  // ==========================================
  getListe(): Observable<any> {
    // 🟢 Zidna l-Payload w l-Headers
    return this.http.post(`${this.apiUrl}/liste`, { 
        residence_id: this.getActivePropId() 
    }, {
        headers: this.getHeaders(),
        withCredentials: true 
    });
  }

  // ==========================================
  // 2. Ajouter une nouvelle clé
  // ==========================================
  ajouter(payload: any): Observable<any> {
    // 🟢 Zidna residence_id f l-payload
    payload.residence_id = this.getActivePropId();
    return this.http.post(`${this.apiUrl}/ajouter`, payload, {
        headers: this.getHeaders(),
        withCredentials: true
    });
  }

  // ==========================================
  // 3. Modifier une clé existante
  // ==========================================
  modifier(payload: any): Observable<any> {
    // 🟢 Zidna residence_id f l-payload
    payload.residence_id = this.getActivePropId();
    return this.http.post(`${this.apiUrl}/modifier`, payload, {
        headers: this.getHeaders(),
        withCredentials: true
    });
  }

  // ==========================================
  // 4. Supprimer une clé
  // ==========================================
  supprimer(cleId: number): Observable<any> {
    return this.http.post(`${this.apiUrl}/supprimer`, { 
      cle_id: cleId,
      residence_id: this.getActivePropId() // 🟢
    }, {
        headers: this.getHeaders(),
        withCredentials: true
    });
  }
}