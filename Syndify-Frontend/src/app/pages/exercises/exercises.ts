import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router, RouterModule } from '@angular/router'; 
import { PageHeader } from '../../components/page-header/page-header';
import { FormsModule } from '@angular/forms';
import { ExerciceService } from '../../services/exercice'; 
import { CleRepartitionService } from '../../services/cle-repartition'; 

@Component({
  selector: 'app-exercises',
  standalone: true,
  imports: [CommonModule, PageHeader, FormsModule, RouterModule],
  templateUrl: './exercises.html',
})
export class Exercises implements OnInit {
  
  isLoading: boolean = false;
  isAddModalOpen: boolean = false;
  isSubmitting: boolean = false;

  activeDropdown: string | null = null;
  isDeleteModalOpen: boolean = false;
  exerciceToDeleteId: string | null = null;
  residenceInfo = { nom: '...', adresse: '...' };

  exercices: any[] = []; 

  newExercice: any = {
    se_identifier: null, 
    start_date: '',
    end_date: '',
    periode: 'trimestre', 
    budget_previsionnel_total: 0,
    budget_travaux_total: 0,
    list_cles_previsionnel: [],
    list_cles_travaux: []
  };

  showNotification: boolean = false;
  notificationMessage: string = '';
  notificationType: 'success' | 'error' = 'success';
  notificationTimeout: any;

  constructor(
    private cdr: ChangeDetectorRef,
    private exerciceService: ExerciceService,
    private cleService: CleRepartitionService,
    private route: ActivatedRoute ,
    private router: Router
  ) {}

  ngOnInit() {
    this.chargerExercices();
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

  // 🟢 1. تعديل démarrage باش يقرا اليرور
  demarrerExercice(ex: any) {
    this.closeDropdown();
    this.exerciceService.demarrer(ex.se_identifier).subscribe({
      next: (res: any) => {
        if (res.success) {
          this.showToast(res.message || "Exercice démarré.", 'success');
          this.chargerExercices();
        } else {
          this.showToast(res.message || "Opération refusée.", 'error');
        }
      },
      error: (err: any) => {
        const errorMessage = err.error?.message || err.message || "Erreur lors du démarrage.";
        this.showToast(errorMessage, 'error');
      }
    });
  }

  chargerExercices() {
    this.isLoading = true;
    this.exerciceService.getListe().subscribe({
      next: (res: any) => {
        if (res.success) {
          this.exercices = res.data;
          if (res.residence) {
            this.residenceInfo = res.residence;
          }
        }
        this.isLoading = false;
        this.cdr.detectChanges();
      },
      error: (err: any) => {
        console.error('❌ Erreur:', err);
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }
  
  toggleDropdown(id: string, event: Event) {
    event.stopPropagation();
    this.activeDropdown = this.activeDropdown === id ? null : id;
    this.cdr.detectChanges();
  }

  closeDropdown() {
    this.activeDropdown = null;
    this.cdr.detectChanges();
  }

  // ==================== AJOUT / MODIFICATION ====================
  openAddModal() {
    this.closeDropdown();
    this.newExercice = {
      se_identifier: null,
      start_date: '',
      end_date: '',
      periode: 'trimestre',
      budget_previsionnel_total: 0,
      budget_travaux_total: 0,
      list_cles_previsionnel: [],
      list_cles_travaux: []
    };
    this.isAddModalOpen = true;
    this.fetchClesRepartition();
  }

  openEditModal(ex: any) {
    this.closeDropdown(); 

    const prevCles = ex.cles_previsionnel || ex.list_cles_previsionnel || ex.charges_previsionnelles || [];
    const travCles = ex.cles_travaux || ex.list_cles_travaux || ex.charges_travaux || [];

    let calculTotalPrev = 0;
    prevCles.forEach((item: any) => {
        calculTotalPrev += Number(item.budget || item.montant || item.amount || 0);
    });

    let calculTotalTrav = 0;
    travCles.forEach((item: any) => {
        calculTotalTrav += Number(item.budget || item.montant || item.amount || 0);
    });

    this.newExercice = {
      ...ex,
      start_date: ex.start_date ? new Date(ex.start_date).toISOString().split('T')[0] : '',
      end_date: ex.end_date ? new Date(ex.end_date).toISOString().split('T')[0] : '',
      periode: (ex.periode || ex.period || 'trimestre').toLowerCase(),
      budget_previsionnel_total: Number(ex.budget_previsionnel_total || ex.budget_previsionnel || ex.total_previsionnel) || calculTotalPrev,
      budget_travaux_total: Number(ex.budget_travaux_total || ex.budget_travaux || ex.total_travaux) || calculTotalTrav,
      list_cles_previsionnel: [],
      list_cles_travaux: []
    };

    this.isAddModalOpen = true; 
    this.fetchClesRepartition(ex); 
  }

  closeAddModal() {
    this.isAddModalOpen = false;
    this.cdr.detectChanges();
  }

  fetchClesRepartition(exerciceAEditer: any = null) {
    this.cleService.getListe().subscribe({
      next: (res: any) => {
        if (res.success) {
          const keysFromApi = res.data;
          
          this.newExercice.list_cles_previsionnel = keysFromApi.map((k: any) => {
            let montantSauvegardé = 0;
            if (exerciceAEditer) {
              const clesPrev = exerciceAEditer.cles_previsionnel || exerciceAEditer.list_cles_previsionnel || exerciceAEditer.charges_previsionnelles || [];
              const findKey = clesPrev.find((item: any) => 
                (item.cle_repartition_id || item.cle_id || item.id) === k.id
              );
              if (findKey) montantSauvegardé = findKey.budget || findKey.montant || findKey.amount || 0;
            }
            return { cle_id: k.id, nom: k.nom, montant: montantSauvegardé };
          });
          
          this.newExercice.list_cles_travaux = keysFromApi.map((k: any) => {
            let montantSauvegardé = 0;
            if (exerciceAEditer) {
              const clesTrav = exerciceAEditer.cles_travaux || exerciceAEditer.list_cles_travaux || exerciceAEditer.charges_travaux || [];
              const findKey = clesTrav.find((item: any) => 
                 (item.cle_repartition_id || item.cle_id || item.id) === k.id
              );
              if (findKey) montantSauvegardé = findKey.budget || findKey.montant || findKey.amount || 0;
            }
            return { cle_id: k.id, nom: k.nom, montant: montantSauvegardé };
          });
          
          this.cdr.detectChanges();
        }
      },
      error: (err) => console.error('❌ Erreur API Clés:', err)
    });
  }

  calculateRemaining(type: 'previsionnel' | 'travaux'): number {
    const total = type === 'previsionnel' ? this.newExercice.budget_previsionnel_total : this.newExercice.budget_travaux_total;
    const list = type === 'previsionnel' ? this.newExercice.list_cles_previsionnel : this.newExercice.list_cles_travaux;
    const sum = list.reduce((acc: number, item: any) => acc + (Number(item.montant) || 0), 0);
    return Number((total - sum).toFixed(2));
  }

  // 🟢 2. تعديل الحفظ باش يقرا اليرور إلا كان success false
  submitExercice() {
    if (this.calculateRemaining('previsionnel') !== 0 || this.calculateRemaining('travaux') !== 0) {
      this.showToast("Le reste à répartir doit être égal à 0.", 'error');
      return;
    }

    this.isSubmitting = true;
    
    const request = this.newExercice.se_identifier 
      ? this.exerciceService.modifier(this.newExercice)
      : this.exerciceService.ajouter(this.newExercice);

    request.subscribe({
      next: (res: any) => {
        if (res.success) {
          this.showToast(res.message || "Opération réussie.", 'success');
          this.closeAddModal();
          this.chargerExercices(); 
        } else {
          this.showToast(res.message || "Opération refusée.", 'error');
        }
        this.isSubmitting = false;
        this.cdr.detectChanges();
      },
      error: (err: any) => {
        const errorMessage = err.error?.message || err.message || "Erreur lors de l'enregistrement.";
        this.showToast(errorMessage, 'error');
        this.isSubmitting = false;
        this.cdr.detectChanges();
      }
    });
  }

  supprimerExercice(id: string) {
    this.closeDropdown();
    this.exerciceToDeleteId = id;
    this.isDeleteModalOpen = true;
    this.cdr.detectChanges();
  }

  closeDeleteModal() {
    this.isDeleteModalOpen = false;
    this.exerciceToDeleteId = null;
    this.cdr.detectChanges();
  }

  // 🟢 3. تعديل الحذف باش يبين اليرور بلاصة ميكتبو غير فـ Console
  confirmerSuppression() {
    if (!this.exerciceToDeleteId) return;
    
    this.exerciceService.supprimer(this.exerciceToDeleteId).subscribe({
      next: (res: any) => {
        if (res.success) {
          this.showToast("Exercice supprimé.", 'success');
          this.closeDeleteModal();
          this.chargerExercices();
        } else {
          this.showToast(res.message || "Suppression refusée.", 'error');
          this.closeDeleteModal();
        }
      },
      error: (err: any) => {
        const errorMessage = err.error?.message || err.message || "Erreur lors de la suppression.";
        this.showToast(errorMessage, 'error');
        this.closeDeleteModal();
        this.cdr.detectChanges();
      }
    });
  }

  cloturerExercice(ex: any) {
    this.closeDropdown();
    
    const idS7i7 = ex.se_identifier || ex.id || ex.identifier;
    
    if (idS7i7) {
       this.router.navigate(['/cloture', idS7i7]).then(success => {
         if (!success) {
           this.router.navigate(['/financement/exercices/cloture', idS7i7]).then(success2 => {
              if (!success2) {
                 this.showToast("Erreur de navigation vers la clôture.", 'error');
              }
           });
         }
       }).catch(err => console.error("Navigation error:", err));
       
    } else {
       this.showToast("Erreur: ID de l'exercice introuvable.", 'error');
    }
  }
}