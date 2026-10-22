import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PolitiqueProtectionDonnees } from './politique-protection-donnees';

describe('PolitiqueProtectionDonnees', () => {
  let component: PolitiqueProtectionDonnees;
  let fixture: ComponentFixture<PolitiqueProtectionDonnees>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [PolitiqueProtectionDonnees]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PolitiqueProtectionDonnees);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
