import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule, Location } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { PageHeader } from '../../components/page-header/page-header';
import { ClotureService } from '../../services/cloture'; 

@Component({
  selector: 'app-cloture',
  standalone: true,
  imports: [CommonModule, FormsModule, PageHeader],
  templateUrl: './cloture.html'
})
export class Cloture implements OnInit {
  
  activeTab: 'previsionnel' | 'travaux' = 'previsionnel';
  isLoading = true;
  isSaving = false;

  spIdentifier: string = '';
  seIdentifier: string = '';

  actionsForm = {
    prev: { reste_choice: '', du_choice: '', send_reminders: false, date_limit_reminders: '' },
    trav: { reste_choice: '', du_choice: '', send_reminders: false, date_limit_reminders: '' }
  };

  data: any = null;
  residenceInfo: any = { nom: 'Chargement...', adresse: '...' };

  // 🟢 VARIABLES DYAL L-POP-UP & MODAL
  showNotification: boolean = false;
  notificationMessage: string = '';
  notificationType: 'success' | 'error' = 'success';
  notificationTimeout: any;

  isConfirmModalOpen: boolean = false; // Bash n-3ewdou confirm()

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private cdr: ChangeDetectorRef,
    private clotureService: ClotureService,
    private location: Location
  ) {}

ngOnInit() {
    this.seIdentifier = this.route.snapshot.paramMap.get('se_id') || 'EX-2026-TEST';
    // 🟢 Bdelna 'propriete_id' b 'active_propriete_id' bach tkon b7al ga3 les pages
    this.spIdentifier = localStorage.getItem('active_propriete_id') || 'SP-87248712'; 

    this.chargerDonnees();
  }

  // 🟢 FONCTION DYAL L-POP-UP
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

  switchTab(tab: 'previsionnel' | 'travaux') {
    this.activeTab = tab;
  }

  chargerDonnees() {
    this.isLoading = true;
    this.clotureService.getClotureData(this.spIdentifier, this.seIdentifier).subscribe({
      next: (res: any) => {
        if (res.success) {
          const d = res.data;
          this.residenceInfo = res.residence;

          this.data = {
            previsionnel: {
              planifie: d.previsionnel.resume,
              cles: d.previsionnel.cles,
              exceptionnel: d.previsionnel.exceptionnel,
              appels_exceptionnels: d.previsionnel.exceptionnel.appels,
              grandTotal: {
                montant: d.grand_total.budget,
                encaissements: d.grand_total.encaissements,
                depenses: d.grand_total.depenses,
                reste: d.grand_total.reste,
                du: d.grand_total.du
              }
            },
            travaux: {
              planifie: d.travaux.resume,
              cles: [],
              exceptionnel: { budget: 0, encaissements: 0 },
              appels_exceptionnels: [],
              grandTotal: {
                montant: d.grand_total.budget,
                encaissements: d.grand_total.encaissements,
                depenses: d.grand_total.depenses,
                reste: d.grand_total.reste,
                du: d.grand_total.du
              }
            }
          };

          if (d.cloture_saved_data) {
            const saved = d.cloture_saved_data;
            this.actionsForm.prev.reste_choice = saved.reste_choice_prev ? saved.reste_choice_prev.toString() : '';
            this.actionsForm.prev.du_choice = saved.du_choice_prev ? saved.du_choice_prev.toString() : '';
            this.actionsForm.prev.send_reminders = saved.send_reminders_prev === 1;
            
            this.actionsForm.trav.reste_choice = saved.reste_choice_trav ? saved.reste_choice_trav.toString() : '';
            this.actionsForm.trav.du_choice = saved.du_choice_trav ? saved.du_choice_trav.toString() : '';
            this.actionsForm.trav.send_reminders = saved.send_reminders_trav === 1;
          }
        }
        this.isLoading = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        console.error("Erreur API:", err);
        // 🟢 FIX: 7iyedna alert
        this.showToast(err.error?.message || "Erreur lors du chargement des données.", "error");
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }

  enregistrerCloture() {
    const payload = this.preparerPayload();
    this.isSaving = true;

    this.clotureService.enregistrerBrouillon(payload).subscribe({
      next: (res) => {
        if (res.success) {
          // 🟢 FIX: 7iyedna alert
          this.showToast("Clôture enregistrée en brouillon avec succès !", "success");
        }
        this.isSaving = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        // 🟢 FIX: 7iyedna alert
        this.showToast(err.error?.message || "Erreur lors de la sauvegarde.", "error");
        this.isSaving = false;
        this.cdr.detectChanges();
      }
    });
  }

  retourListe() {
    this.location.back(); 
  }

  // 🟢 FIX: 3wedna confirm() b Modal aniiqa
  ouvrirFinalisation() {
    this.isConfirmModalOpen = true;
  }

  fermerConfirmModal() {
    this.isConfirmModalOpen = false;
  }

  confirmerFinalisation() {
    this.isConfirmModalOpen = false;
    const payload = this.preparerPayload();
    this.isSaving = true;

    this.clotureService.finaliserCloture(payload).subscribe({
      next: (res) => {
        if (res.success) {
          this.showToast("Clôture finalisée avec succès ! Document généré.", "success");
          this.router.navigate(['/exercice']); 
        }
        this.isSaving = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        this.showToast(err.error?.message || "Erreur lors de la finalisation.", "error");
        this.isSaving = false;
        this.cdr.detectChanges();
      }
    });
  }

  private preparerPayload() {
    return {
      sp_identifier: this.spIdentifier,
      se_identifier: this.seIdentifier,
      
      reste_choice_prev: this.actionsForm.prev.reste_choice ? parseInt(this.actionsForm.prev.reste_choice) : null,
      du_choice_prev: this.actionsForm.prev.du_choice ? parseInt(this.actionsForm.prev.du_choice) : null,
      send_reminders_prev: this.actionsForm.prev.send_reminders,
      date_limit_reminders_prev: this.actionsForm.prev.date_limit_reminders,

      reste_choice_trav: this.actionsForm.trav.reste_choice ? parseInt(this.actionsForm.trav.reste_choice) : null,
      du_choice_trav: this.actionsForm.trav.du_choice ? parseInt(this.actionsForm.trav.du_choice) : null,
      send_reminders_trav: this.actionsForm.trav.send_reminders,
      date_limit_reminders_trav: this.actionsForm.trav.date_limit_reminders,
    };
  }
}