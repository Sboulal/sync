import { TestBed } from '@angular/core/testing';

import { PageTitle } from './page-title';

describe('PageTitle', () => {
  let service: PageTitle;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(PageTitle);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
