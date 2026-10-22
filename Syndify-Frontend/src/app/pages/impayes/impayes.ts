import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { RouterModule } from '@angular/router'; 
import { PageHeader } from '../../components/page-header/page-header';

@Component({
  selector: 'app-impayes',
  standalone: true,
  imports: [CommonModule, PageHeader, RouterModule], 
  templateUrl: './impayes.html',
})
export class Impayes implements OnInit {
  isLoading = false;
  isSending: string | null = null; 
  
  impayesList: any[] = [];
  totalImpayes: number = 0;
  residenceInfo = { nom: '...', adresse: '...' };

  // ── Toast ──
  showNotification = false;
  notificationMessage = '';
  notificationType: 'success' | 'error' = 'success';
  notificationTimeout: any;

  // ── Confirm modal ──
  isConfirmModalOpen = false;
  suIdentifierToRelance: string | null = null; 

  private baseUrl = 'http://51.178.87.234 :8021/api';

  constructor(
    private http: HttpClient,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit() {
    this.chargerImpayes();
  }

  // 🟢 Helper باش نجبدو الـ ID من LocalStorage
  private getActivePropId() {
    return localStorage.getItem('active_propriete_id') || '';
  }

  // 🟢 Helper باش نصيفطو الـ Headers
  private getHeaders() {
    return new HttpHeaders({
      'residence_id': this.getActivePropId()
    });
  }

  // ── Toast ──
  showToast(message: string, type: 'success' | 'error' = 'success') {
    this.notificationMessage = message;
    this.notificationType = type;
    this.showNotification = true;
    this.cdr.detectChanges();

    if (this.notificationTimeout) clearTimeout(this.notificationTimeout);
    this.notificationTimeout = setTimeout(() => {
      this.showNotification = false;
      this.cdr.detectChanges();
    }, 3000);
  }

  // ── Chargement ──
  chargerImpayes() {
    this.isLoading = true;

    // 🟢 كنزيدو residence_id فـ Payload والـ Headers
    this.http.post(`${this.baseUrl}/impayes/liste`, {
        residence_id: this.getActivePropId()
    }, { 
        headers: this.getHeaders(), 
        withCredentials: true 
    }).subscribe({
      next: (res: any) => {
        if (res.success) {
          this.impayesList  = res.data          || [];
          this.totalImpayes = res.total_impayes || 0;
          if (res.residence) this.residenceInfo = res.residence;
        } else {
          this.impayesList = [];
        }
        this.isLoading = false;
        this.cdr.detectChanges();
      },
      error: () => {
        this.showToast("Erreur lors du chargement des impayés.", "error");
        this.impayesList = [];
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }

  // ── Confirm modal ──
  ouvrirConfirmRelance(su_identifier: string) {
    if (!su_identifier) {
      this.showToast("Erreur : L'identifiant du copropriétaire est introuvable.", "error");
      return;
    }
    this.suIdentifierToRelance = su_identifier;
    this.isConfirmModalOpen = true;
    this.cdr.detectChanges();
  }

  fermerConfirmModal() {
    this.isConfirmModalOpen = false;
    this.suIdentifierToRelance = null;
    this.cdr.detectChanges();
  }

  confirmerRelance() {
    if (!this.suIdentifierToRelance) return;
    this.fermerConfirmModal();
    this.relancer(this.suIdentifierToRelance);
  }

  // ── Relancer ──
  relancer(su_identifier: string) {
    if (!su_identifier) {
      this.showToast("Erreur : L'identifiant du copropriétaire est introuvable.", "error");
      return;
    }

    console.log("📦 su_identifier envoyé au backend :", su_identifier);

    this.isSending = su_identifier;
    this.cdr.detectChanges();

    // 🟢 كنزيدو residence_id فـ Payload
    const payload = { 
        su_identifier: su_identifier,
        residence_id: this.getActivePropId()
    };

    // 🟢 كنزيدو הـ Headers
    this.http.post(`${this.baseUrl}/impayes/envoyer-rappel`, payload, { 
        headers: this.getHeaders(), 
        withCredentials: true 
    }).subscribe({
      next: (res: any) => {
        if (res.success) {
          this.showToast(res.message || "Rappel envoyé avec succès.", "success");
          this.chargerImpayes();
        } else {
          this.showToast(res.message || "Erreur lors de l'envoi.", "error");
        }
        this.isSending = null;
        this.cdr.detectChanges();
      },
      error: (err) => {
        console.error(err);
        this.showToast(err.error?.message || "Erreur lors de l'envoi.", "error");
        this.isSending = null;
        this.cdr.detectChanges();
      }
    });
  }

  // ── Format ID (UUID compatible) ──
  formatId(id: any): string {
    if (!id) return '';
    const idStr = String(id);
    if (idStr.includes('-')) {
      return 'COP-' + idStr.replace(/-/g, '').slice(-8).toUpperCase();
    }
    return 'COP-' + (Number(id) + 845752).toString().padStart(8, '0');
  }

  // ── Format montant ──
  formatMontant(montant: number): string {
    if (!montant) return '0,00';
    return Math.abs(montant).toLocaleString('fr-FR', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }
}