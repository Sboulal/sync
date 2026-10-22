import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';

@Injectable({ providedIn: 'root' })
export class LotService {
  private baseUrl = 'http://51.178.87.234:8021/api/lots';

  constructor(private http: HttpClient) {}

  // 🟢 Helper باش نجبدو الـ ID من LocalStorage
  private getActivePropId() {
    return localStorage.getItem('active_propriete_id') || '';
  }

  // 🟢 Helper باش نجبدو الـ Headers (كنخليوهم كاحتياط)
  private getHeaders() {
    return new HttpHeaders({
      'residence_id': this.getActivePropId()
    });
  }

  getListe() {
    // 🟢 زدنا residence_id فـ الـ Body ({})
    return this.http.post(`${this.baseUrl}/liste`, { residence_id: this.getActivePropId() }, { 
      headers: this.getHeaders(), 
      withCredentials: true 
    });
  }

  ajouter(payload: any) {
    // 🟢 كنزيدو residence_id فـ الـ Payload قبل ما نصيفطوه
    payload.residence_id = this.getActivePropId();
    return this.http.post(`${this.baseUrl}/ajouter`, payload, { 
      headers: this.getHeaders(), 
      withCredentials: true 
    });
  }

  modifier(payload: any) {
    payload.residence_id = this.getActivePropId();
    return this.http.post(`${this.baseUrl}/modifier`, payload, { 
      headers: this.getHeaders(), 
      withCredentials: true 
    });
  }

  supprimer(id: any) {
    // 🟢 كنزيدو residence_id مع الـ lot_id
    return this.http.post(`${this.baseUrl}/supprimer`, { 
      lot_id: id, 
      residence_id: this.getActivePropId() 
    }, { 
      headers: this.getHeaders(), 
      withCredentials: true 
    });
  }
}