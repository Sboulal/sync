import { Component, OnInit, ChangeDetectorRef } from '@angular/core'; 
import { CommonModule } from '@angular/common';
import { RouterModule, NavigationEnd, Router } from '@angular/router';
import { HttpClient, HttpHeaders } from '@angular/common/http'; // 🟢 زدنا HttpHeaders
import { filter } from 'rxjs/operators';

@Component({
  selector: 'app-main-layout',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './main-layout.html',
  styleUrls: ['./main-layout.css'],
})
export class MainLayout implements OnInit {
  isSidebarCollapsed: boolean = false;
  openMenus: { [key: string]: boolean } = {};
  activeItem: string = ''; 
  
  residenceNom: string = 'Chargement...'; 

  currentVersion: string = '1.12.42';
  shortVersion: string = 'v1.12';

  isReleaseNotesOpen: boolean = false;
  releaseNotes = [
    { type: 'Nouveau', desc: 'Ajout de la gestion des relances automatiques pour les impayés.' },
    { type: 'Nouveau', desc: 'Possibilité de télécharger les reçus et relevés en format PDF.' },
    { type: 'Amélioration', desc: 'Design unifié pour les états vides (Empty states) dans tous les tableaux.' },
    { type: 'Correction', desc: 'Correction du bug de superposition des menus déroulants dans la liste des lots.' }
  ];

  constructor(
    private router: Router,
    private http: HttpClient,
    private cdr: ChangeDetectorRef 
  ) {}

  ngOnInit() {
    this.checkActiveRoute();
    
    this.router.events.pipe(
      filter(event => event instanceof NavigationEnd)
    ).subscribe(() => {
      this.checkActiveRoute();
      this.cdr.detectChanges();
    });

    this.chargerInfosResidence();
    this.verifierNouvelleVersion();
    if (window.innerWidth < 768) {
      this.isSidebarCollapsed = true;
    }
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

  verifierNouvelleVersion() {
    const lastSeenVersion = localStorage.getItem('last_seen_version');
    if (lastSeenVersion !== this.currentVersion) {
      this.isReleaseNotesOpen = true;
    }
  }

  isMenuActive(menu: string): boolean {
    const menuItems: { [key: string]: string[] } = {
      'copropriete': ['gestion-lots', 'liste-copros', 'cles', 'impayes'],
      'financement':  ['budgets-depenses', 'appels-de-fonds', 'simulation-budget'],
    };
    return menuItems[menu]?.includes(this.activeItem) ?? false;
  }

  chargerInfosResidence() {
    const token = localStorage.getItem('auth_token');

    if (!token) {
      this.residenceNom = 'Ma copropriété';
      return;
    }

    // 🟢 FIX: رديناها POST وصيفطنا residence_id باش نجيبو السمية الصحيحة
    const payload = {
        type_affichage: 'tous',
        residence_id: this.getActivePropId()
    };

    this.http.post('http://51.178.87.234 :8021/api/coproprietaires/liste', payload, {
        headers: this.getHeaders(),
        withCredentials: true
    }).subscribe({
      next: (res: any) => {
        if (res.success && res.residence && res.residence.nom) {
          this.residenceNom = res.residence.nom;
          // 🟢 كنسجلو السمية فـ LocalStorage باش הـ PageHeader (Breadcrumb) يقراها صحيحة
          localStorage.setItem('residence_nom', res.residence.nom);
        } else {
          this.residenceNom = 'Ma copropriété';
        }
        this.cdr.detectChanges(); 
      },
      error: (err) => {
        console.error("❌ Erreur chargement infos résidence mn l-Backend:", err);
        // 🟢 كاحتياط، نقراوها من LocalStorage إيلا كان شي إيرور
        this.residenceNom = localStorage.getItem('residence_nom') || 'Ma copropriété';
        this.cdr.detectChanges(); 
      }
    });
  }

  checkActiveRoute() {
    const currentUrl = this.router.url;

    if (currentUrl.includes('/dashboard')) {
      this.activeItem = 'dashboard';
      this.openMenus = {}; 
    } 
    else if (currentUrl.includes('/gestion-lots') || currentUrl.includes('/liste-coproprietes') || currentUrl.includes('/cles-de-repartition') || currentUrl.includes('/impayes')) {
      if (currentUrl.includes('/gestion-lots')) this.activeItem = 'gestion-lots';
      if (currentUrl.includes('/liste-coproprietes')) this.activeItem = 'liste-copros';
      if (currentUrl.includes('/cles-de-repartition')) this.activeItem = 'cles';
      if (currentUrl.includes('/impayes')) this.activeItem = 'impayes';
      this.openMenus['copropriete'] = true; 
    }
    else if (currentUrl.includes('/budgets-depenses') || currentUrl.includes('/appels-de-fonds') || currentUrl.includes('/simulation-budget')) {
      if (currentUrl.includes('/budgets-depenses')) this.activeItem = 'budgets-depenses';
      if (currentUrl.includes('/appels-de-fonds')) this.activeItem = 'appels-de-fonds';
      if (currentUrl.includes('/simulation-budget')) this.activeItem = 'simulation-budget';
      this.openMenus['financement'] = true; 
    }
    else if (currentUrl.includes('/exercice')  || currentUrl.includes('cloture')) {
      if (currentUrl.includes('/exercice')) this.activeItem = 'exercice';
      if (currentUrl.includes('cloture')) this.activeItem = 'cloture';
      this.openMenus['Exercice & clôtures'] = true; 
    }
    else if (currentUrl.includes('/documents')) {
      this.activeItem = 'documents';
      this.openMenus['documents'] = true;
    }
  }

  toggleSidebar() {
    this.isSidebarCollapsed = !this.isSidebarCollapsed;
  }

  toggleMenu(menu: string, event?: Event) {
    if (event) {
      event.preventDefault(); 
    }
    const etaitOuvert = this.openMenus[menu];
    this.openMenus = {}; 
    if (!etaitOuvert) {
      this.openMenus[menu] = true; 
    }
  }

  setActive(item: string) {
    this.activeItem = item;
    if (item === 'dashboard') {
      this.openMenus = {}; 
    }
    
    if (window.innerWidth < 768) {
      this.isSidebarCollapsed = true;
    }
  }

  openReleaseNotes(event: Event) {
    event.preventDefault();
    this.isReleaseNotesOpen = true;
  }

  closeReleaseNotes() {
    this.isReleaseNotesOpen = false;
    localStorage.setItem('last_seen_version', this.currentVersion);
  }

  logout() {
    const token = localStorage.getItem('auth_token');
    
    if (token) {
      this.http.post('http://51.178.87.234 :8021/api/logout', {}, {
        headers: { Authorization: `Bearer ${token}` }
      }).subscribe({
        next: () => this.clearSessionAndRedirect(),
        error: () => this.clearSessionAndRedirect() 
      });
    } else {
      this.clearSessionAndRedirect();
    }
  }

  clearSessionAndRedirect() {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user_id');
    localStorage.removeItem('user_role');
    localStorage.removeItem('active_propriete_id');
    localStorage.removeItem('residence_nom');
    
    this.router.navigate(['/login']);
  }
}