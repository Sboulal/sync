import { Routes } from '@angular/router';
import { Login } from './Authentification/login/login';
import { MainLayout } from './main-layout/main-layout';
import { guestGuard } from './core/guards/guest-guard';
import { authGuard } from './core/guards/auth-guard';
import { GestionLots } from './pages/gestion-lots/gestion-lots';
import { ListeCoproprietes } from './pages/liste-coproprietes/liste-coproprietes';
import { Cle } from './pages/cle/cle';
import { Dashboard } from './pages/dashboard/dashboard';
import { HistoriqueCoproprietaire } from './pages/historique-coproprietaire/historique-coproprietaire';
import {  Exercises } from './pages/exercises/exercises';
import { SimulationBudget } from './pages/simulerbudget/simulerbudget';
import { BudgetsOperations } from './pages/budgetsdepenses/budgetsdepenses';
import { Appelsfonds } from './pages/appelsfonds/appelsfonds';
import { AppelsFondsDetails } from './pages/appels-fonds-details/appels-fonds-details';
import { Cloturedetails } from './pages/cloturedetails/cloturedetails';
import { Impayes } from './pages/impayes/impayes';
import { Documents } from './pages/documents/documents';
import { Cloture } from './pages/cloture/cloture';
import { Parametres } from './pages/parametrage/parametres';
import { Landing } from './pages/landing/landing';
import { Contact } from './pages/contact/contact';
import { Home } from './pages/home/home';
import { PolitiqueProtectionDonnees } from './pages/politique-protection-donnees/politique-protection-donnees';
export const routes: Routes = [
  // 🟢 السطر السحري: زدنا pathMatch: 'full' هنا باش مايبقاش يخطف الروابط الأخرى
  { path: '', component: Landing, pathMatch: 'full' },
  
  { path: 'contact', component: Contact },
  { path: 'politique-protection-donnees', component: PolitiqueProtectionDonnees },

  // ==========================================
  // 🔓 ROUTES PUBLIQUES (Non connectés)
  // ==========================================
  { path: 'login', component: Login  },
  {path : 'home', component: Home},

  // ==========================================
  // 🔐 ROUTES PRIVÉES (Sécurisées avec Layout)
  // ==========================================
  {
    path: '', 
    component: MainLayout, 
    
    children: [
      { path: '', redirectTo: 'dashboard', pathMatch: 'full' }, 
      
      { path: 'dashboard', component: Dashboard },
      { path: 'gestion-lots', component: GestionLots },
      { path: 'gestion-lots/:id', component: GestionLots }, 

      { path: 'liste-coproprietes', component: ListeCoproprietes },
      { path: 'residence/:id/coproprietaires', component: ListeCoproprietes },
      { path: 'cles-de-repartition', component: Cle },
      { path: 'coproprietaires/historique/:user_id', component: HistoriqueCoproprietaire },
      { path: 'exercice', component: Exercises }, 
      { path: 'simulation-budget', component: SimulationBudget },
      { path: 'budgets-depenses', component: BudgetsOperations },
      { path: 'appels-de-fonds', component: Appelsfonds },
      
      // 🟢 هنا حيدت التكرار اللي كان (كانت وحدة د Details غادية لـ Appelsfonds بالخطأ)
      { path: 'financement/appels-fonds/details/:id', component: AppelsFondsDetails },
      
      { path: 'clotures/details/:id', component: Cloturedetails },
      { path: 'impayes', component: Impayes },
      { path: 'documents', component: Documents }, 
      { path: 'cloture/:se_id', component: Cloture },
      { path: 'parametrage', component: Parametres },
    ]
  },

  // ==========================================
  // 🔄 Erreur 404 (Route non trouvée)
  // ==========================================
  { path: '**', redirectTo: 'login' }
];