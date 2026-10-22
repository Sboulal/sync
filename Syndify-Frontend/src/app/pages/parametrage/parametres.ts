import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms'; 
import { HttpClient } from '@angular/common/http'; // 🟢 7iyedna HttpHeaders
import { PageHeader } from '../../components/page-header/page-header';

@Component({
  selector: 'app-parametres',
  standalone: true,
  imports: [CommonModule, FormsModule, PageHeader],
  templateUrl: './parametres.html',
})
export class Parametres implements OnInit {
  isLoading: boolean = false;

  syndic = { nom: '', telephone: '', email: '' };
  residence = { nom: '', adresse: '', ville: '', code_postal: '', siret: '' };

  showNotification: boolean = false;
  notificationMessage: string = '';
  notificationType: 'success' | 'error' = 'success';
  notificationTimeout: any;

  constructor(private http: HttpClient, private cdr: ChangeDetectorRef) {}

  ngOnInit() {
    this.chargerParametres();
  }

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

  // 1. Njbdou d-data mlli kat-7ell l-page
  chargerParametres() {
    this.isLoading = true;
    
    const currentResidenceId = localStorage.getItem('active_propriete_id') || ''; 

    // 🟢 Sifetna l-ID f 'params' (Query String: ?residence_id=...) f blaset Header
    this.http.get('http://51.178.87.234 :8021/api/parametres', { 
      params: { residence_id: currentResidenceId } 
    }).subscribe({
      next: (res: any) => {
        if (res.success) {
          this.syndic = res.syndic;
          this.residence = res.residence;
        }
        this.isLoading = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        console.error("❌ Erreur chargement paramètres:", err);
        this.showToast("Erreur lors du chargement des paramètres.", "error");
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }

  // 2. N-siftou d-data j-jdida l-backend
  sauvegarderParametres() {
    if (!this.syndic.nom || !this.syndic.email || !this.residence.nom) {
      this.showToast("Veuillez remplir les champs obligatoires (*).", "error");
      return;
    }

    this.isLoading = true;
    const currentResidenceId = localStorage.getItem('active_propriete_id') || ''; 
    
    const payload = {
      syndic: this.syndic,
      residence: this.residence,
      residence_id: currentResidenceId // 🟢 Zidnaha f l-Body dyal l-POST nichan
    };

    // 🟢 7iyedna l-headers mn hna 7it siftnaha f l-payload
    this.http.post('http://51.178.87.234 :8021/api/parametres/update', payload).subscribe({
      next: (res: any) => {
        if (res.success) {
          this.showToast("Paramètres enregistrés avec succès !", "success");
        } else {
          this.showToast(res.message || "Erreur lors de la sauvegarde.", "error");
        }
        this.isLoading = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        console.error("❌ Erreur sauvegarde:", err);
        this.showToast("Erreur lors de la sauvegarde.", "error");
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }
}