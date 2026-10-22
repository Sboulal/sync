import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http'; 
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class CoproprietaireService {
  
  private baseUrl = 'http://51.178.87.234 :8021/api'; 
  private apiUrl = `${this.baseUrl}/coproprietaires`;

  constructor(private http: HttpClient) {}

  // 🟢 Helper باش نجبدو الـ ID من LocalStorage
  private getActivePropId() {
    return localStorage.getItem('active_propriete_id') || '';
  }

  // 🟢 Helper باش نصيفطو الـ Headers (احتياط باش الباكاند يقراه باي طريقة)
  private getHeaders() {
    return new HttpHeaders({
      'residence_id': this.getActivePropId()
    });
  }

  // 1. Liste
  getListe(typeAffichage: string = 'actif', lastId?: number) {
    const payload = { 
        type_affichage: typeAffichage,
        last_id: lastId,
        residence_id: this.getActivePropId() // 🟢 زدنا الـ ID فـ Payload
    };
    return this.http.post(`${this.apiUrl}/liste`, payload, { 
      headers: this.getHeaders(), 
      withCredentials: true 
    });
  }

  // 2. Désactiver
  desactiver(userId: string): Observable<any> {
    return this.http.post(`${this.apiUrl}/desactiver`, { 
      user_id: userId,
      residence_id: this.getActivePropId() // 🟢
    }, { 
      headers: this.getHeaders(), 
      withCredentials: true 
    });
  }

  // 3. Ajouter ou Modifier
  ajouter(coproData: any) {
    // 🟢 كنزيدو الـ ID ديريكت فـ الداتا اللي مبعوتة
    coproData.residence_id = this.getActivePropId(); 
    return this.http.post(`${this.apiUrl}/ajouter`, coproData, { 
      headers: this.getHeaders(), 
      withCredentials: true 
    });
  }

  // 4. Supprimer
  supprimer(userId: string): Observable<any> {
    return this.http.post(`${this.apiUrl}/supprimer`, { 
      user_id: userId,
      residence_id: this.getActivePropId() // 🟢
    }, { 
      headers: this.getHeaders(), 
      withCredentials: true 
    });
  }

  // 5. Historique
  getHistorique(userId: string, exerciceId?: string): Observable<any> {
    return this.http.post(`${this.apiUrl}/historique`, {
      user_id: userId,
      se_identifier: exerciceId,
      residence_id: this.getActivePropId() // 🟢
    }, { 
      headers: this.getHeaders(), 
      withCredentials: true 
    });
  }
}