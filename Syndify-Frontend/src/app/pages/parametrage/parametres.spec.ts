import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Parametrage } from './parametrage';

describe('Parametrage', () => {
  let component: Parametrage;
  let fixture: ComponentFixture<Parametrage>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [Parametrage]
    })
    .compileComponents();

    fixture = TestBed.createComponent(Parametrage);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
