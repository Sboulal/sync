import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { PageHeader } from '../../components/page-header/page-header';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { ActivatedRoute, Router } from '@angular/router';
import { FormsModule } from '@angular/forms'; 

@Component({
  selector: 'app-appelsfonds',
  standalone: true,
  imports: [CommonModule, PageHeader, FormsModule], 
  templateUrl: './appelsfonds.html',
})
export class Appelsfonds implements OnInit {

  activeTab: 'previsionnel' | 'travaux' = 'previsionnel';
  listeCles: any[] = [];
  isConfirmModalOpen: boolean = false;
  confirmActionType: 'generer' | 'envoyer' | null = null;
  appelToProcess: any = null;
  
  isExerciceMenuOpen: boolean = false;
  
  listeExercices: any[] = []; 
  exercicesActifs: any[] = []; 
  exercicesClos: any[] = [];   
  
  appelsDeFonds: any[] = [];
  appelsPlanifies: any[] = [];
  appelsExceptionnels: any[] = [];
  residenceInfo = { nom: '...', adresse: '...' };
  isModalOpen = false;
  modalType: 'planifie' | 'exceptionnel' = 'planifie';
  clesRepartition: any[] = [];
  newExceptionnel = { title: '', amount: 0, due_date: '', cle_id: '' };

  totalMontant: number = 0;
  isLoading: boolean = false;

  exerciceInfos: any = null; 

  showNotification: boolean = false;
  notificationMessage: string = '';
  notificationType: 'success' | 'error' = 'success';
  notificationTimeout: any;

  private baseUrl = 'http://51.178.87.234 :8021/api';

  constructor(
    private http: HttpClient,
    private cdr: ChangeDetectorRef,
    private router: Router
  ) {}

  ngOnInit() {
    this.chargerClesRepartition().then(() => {
      this.chargerListeExercices().then(() => {  
        this.chargerListeAppels();
      });
    });
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

  get isExerciceActif(): boolean {
    if (!this.exerciceInfos || !this.exerciceInfos.status) return false;
    const status = String(this.exerciceInfos.status).toLowerCase();
    return status === 'en cours' || status === 'en attente';
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

  chargerListeExercices(): Promise<any> {
    return new Promise((resolve) => {
      // 🟢 زدنا residence_id
      this.http.post(`${this.baseUrl}/exercices/liste`, { residence_id: this.getActivePropId() }, { headers: this.getHeaders(), withCredentials: true }).subscribe({
        next: (res: any) => {
          if (res.success && Array.isArray(res.data)) {
            this.listeExercices = res.data.map((ex: any) => ({
              ...ex,
              id: ex.se_identifier || ex.id,
              dateDebut: this.formaterDate(ex.start_date),
              dateFin:   this.formaterDate(ex.end_date),
            }));

            this.exercicesActifs = this.listeExercices.filter(ex => ex.status?.toLowerCase() !== 'clos');
            this.exercicesClos = this.listeExercices.filter(ex => ex.status?.toLowerCase() === 'clos');
          }
          resolve(res);
        },
        error: (err) => {
          console.error("Erreur chargement exercices", err);
          resolve([]);
        }
      });
    });
  }

  changerExercice(id: any) {
    this.isExerciceMenuOpen = false;

    const ex = this.listeExercices.find(e => e.id === id);
    if (ex) {
      this.exerciceInfos = {
        id:           ex.id,
        annee:        new Date(ex.start_date || ex.dateDebut).getFullYear(),
        dateDebut:    ex.dateDebut,
        dateFin:      ex.dateFin,
        status:       ex.status,
        montant_total: ex.budget_previsionnel_total
      };
    }

    this.chargerListeAppels(id);
    this.cdr.detectChanges();
  }

  creerAppelExceptionnel() {
    if (!this.newExceptionnel.title || !this.newExceptionnel.amount || !this.newExceptionnel.due_date || !this.newExceptionnel.cle_id) {
      this.showToast("Veuillez remplir tous les champs obligatoires.", "error");
      return;
    }

    this.isLoading = true;

    // 🟢 زدنا residence_id
    const payload = {
      se_identifier:      this.exerciceInfos.id,
      type_charge:        this.activeTab,
      title:              this.newExceptionnel.title,
      amount:             this.newExceptionnel.amount,
      due_date:           this.newExceptionnel.due_date,
      cle_repartition_id: this.newExceptionnel.cle_id,
      residence_id:       this.getActivePropId()
    };

    this.http.post(`${this.baseUrl}/appels-fonds/ajouter-exceptionnel`, payload, { headers: this.getHeaders(), withCredentials: true }).subscribe({
      next: (res: any) => {
        this.showToast("Succès", "success");
        this.isModalOpen = false;
        this.newExceptionnel = { title: '', amount: 0, due_date: '', cle_id: '' };
        this.chargerListeAppels();
        this.isLoading = false;
      },
      error: (err) => {
        console.error(err);
        this.showToast(err.error?.message || "Erreur 400", "error");
        this.isLoading = false;
      }
    });
  }

  switchTab(tab: 'previsionnel' | 'travaux') {
    this.activeTab = tab;
    this.chargerListeAppels();
  }

  chargerListeAppels(se_identifier?: string) {
    this.isLoading = true;
    const apiUrl = `${this.baseUrl}/appels-fonds/liste`;
    
    // 🟢 زدنا residence_id
    const payload: any = { 
        type_charge: this.activeTab,
        residence_id: this.getActivePropId()
    };

    if (se_identifier) {
      payload.se_identifier = se_identifier;
    } else if (this.exerciceInfos?.id) {
      payload.se_identifier = this.exerciceInfos.id;
    }

    this.http.post(apiUrl, payload, { headers: this.getHeaders(), withCredentials: true }).subscribe({
      next: (res: any) => {
        if (res.success) {
          if (res.residence) this.residenceInfo = res.residence;

          this.appelsDeFonds      = res.data || [];
          this.appelsPlanifies    = this.appelsDeFonds.filter((a: any) => a.sub_type === 'planifie');
          this.appelsExceptionnels = this.appelsDeFonds.filter((a: any) => a.sub_type === 'exceptionnel');

          if (res.exercice) {
            this.exerciceInfos = {
              id:           res.exercice.se_identifier,
              annee:        new Date(res.exercice.start_date).getFullYear(),
              dateDebut:    this.formaterDate(res.exercice.start_date),
              dateFin:      this.formaterDate(res.exercice.end_date),
              montant_total: res.exercice.budget_previsionnel_total,
              status:       res.exercice.status
            };

            if (this.listeExercices.length === 0 && res.exercice) {
              const dummyEx = {
                id:       res.exercice.se_identifier,
                dateDebut: this.formaterDate(res.exercice.start_date),
                dateFin:   this.formaterDate(res.exercice.end_date),
                status:    res.exercice.status,
              };
              
              this.listeExercices = [dummyEx];
              
              if (dummyEx.status?.toLowerCase() === 'clos') {
                  this.exercicesClos = [dummyEx];
              } else {
                  this.exercicesActifs = [dummyEx];
              }
            }
          }
        } else {
          this.appelsDeFonds       = [];
          this.appelsPlanifies     = [];
          this.appelsExceptionnels = [];
        }
        this.isLoading = false;
        this.cdr.detectChanges();
      },
      error: () => {
        this.appelsDeFonds       = [];
        this.appelsPlanifies     = [];
        this.appelsExceptionnels = [];
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }

  formaterDate(dateString: string): string {
    if (!dateString) return '';
    const options: Intl.DateTimeFormatOptions = { day: 'numeric', month: 'short', year: 'numeric' };
    return new Date(dateString).toLocaleDateString('fr-FR', options);
  }

  allerVersDetails(af_identifier: string) {
    this.router.navigate(['/financement/appels-fonds/details', af_identifier]);
  }

  genererAppel(appel: any) {
    this.appelToProcess = appel;
    this.confirmActionType = 'generer';
    this.isConfirmModalOpen = true;
  }

  envoyerAppel(appel: any) {
    this.appelToProcess = appel;
    this.confirmActionType = 'envoyer';
    this.isConfirmModalOpen = true;
  }

  fermerConfirmModal() {
    this.isConfirmModalOpen = false;
    this.appelToProcess = null;
    this.confirmActionType = null;
  }

  confirmerAction() {
    if (!this.appelToProcess || this.isLoading) return;

    this.isLoading = true;
    this.isConfirmModalOpen = false;

    // 🟢 زدنا residence_id
    const payload = {
      se_identifier: this.appelToProcess.se_identifier,
      af_identifier: this.appelToProcess.af_identifier,
      residence_id: this.getActivePropId()
    };

    if (this.confirmActionType === 'generer') {
      this.http.post(`${this.baseUrl}/appels-fonds/generer`, payload, { 
          headers: this.getHeaders(), 
          withCredentials: true, 
          responseType: 'blob' 
      }).subscribe({
        next: (res: Blob) => {
          this.showToast("PDFs générés avec succès.", "success");
          this.chargerListeAppels();

          const blob = new Blob([res], { type: 'application/zip' });
          const url  = window.URL.createObjectURL(blob);
          const a    = document.createElement('a');
          a.style.display = 'none';
          a.href     = url;
          a.download = `Appel_Fonds_${this.appelToProcess.af_identifier}.zip`;
          document.body.appendChild(a);
          a.click();

          setTimeout(() => {
            window.URL.revokeObjectURL(url);
            a.remove();
            this.isLoading = false;
            this.cdr.detectChanges();
          }, 1000);
        },
        error: (err) => {
          if (err.error instanceof Blob) {
            err.error.text().then((text: string) => {
              const parsed = JSON.parse(text);
              this.showToast(parsed.message || "Erreur de génération", "error");
            });
          } else {
            this.showToast("Erreur lors de la génération du PDF", "error");
          }
          this.isLoading = false;
          this.cdr.detectChanges();
        }
      });
    } else if (this.confirmActionType === 'envoyer') {
      this.http.post(`${this.baseUrl}/appels-fonds/envoyer`, { 
          af_identifier: this.appelToProcess.af_identifier,
          residence_id: this.getActivePropId() // 🟢
      }, { headers: this.getHeaders(), withCredentials: true }).subscribe({
        next: (res: any) => {
          if (res.success) {
            this.showToast(res.message || "Appel envoyé avec succès.", "success");
            this.chargerListeAppels();
          }
          this.isLoading = false;
          this.cdr.detectChanges();
        },
        error: (err) => {
          this.showToast(err.error?.message || "Erreur d'envoi", "error");
          this.isLoading = false;
          this.cdr.detectChanges();
        }
      });
    }
  }

  isPeriodeAtteinte(dueDate: string): boolean {
    if (!dueDate) return false;
    return new Date(dueDate) <= new Date();
  }

  chargerClesRepartition(): Promise<any> {
    return new Promise((resolve) => {
      // 🟢 زدنا residence_id
      this.http.post(`${this.baseUrl}/cles-repartition/liste`, { residence_id: this.getActivePropId() }, { headers: this.getHeaders(), withCredentials: true }).subscribe({
        next: (res: any) => {
          if (res.success) this.listeCles = res.data;
          resolve(res);
        },
        error: (err) => {
          console.error("Erreur chargement clés", err);
          resolve([]);
        }
      });
    });
  }

  getNomCle(id: any): string {
    if (!id || !this.listeCles) return '--';
    const cle = this.listeCles.find(c => String(c.id) === String(id));
    return cle ? cle.nom : '--';
  }

  creerAppelsPlanifies() {
    if (!this.exerciceInfos) return;
    
    this.isLoading = true;
    // 🟢 زدنا residence_id
    const payload = {
      se_identifier: this.exerciceInfos.id,
      type_charge:   this.activeTab,
      residence_id:  this.getActivePropId()
    };

    this.http.post(`${this.baseUrl}/appels-fonds/ajouter-planifie`, payload, { headers: this.getHeaders(), withCredentials: true }).subscribe({
      next: (res: any) => {
        if (res.success) {
          this.showToast(res.message || "Appels planifiés mis à jour avec succès.", "success");
          this.chargerListeAppels();
        }
        this.isLoading = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        this.showToast(err.error?.message || "Erreur lors de la génération", "error");
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }
}