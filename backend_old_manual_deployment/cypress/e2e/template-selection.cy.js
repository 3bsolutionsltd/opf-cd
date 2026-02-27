// cypress/e2e/template-selection.cy.js
// Phase 5.4.5: Frontend and UI Testing
// Test template selection, preview, and project creation flows

describe('Template Selection and Project Creation', () => {
  beforeEach(() => {
    // Setup: Login and navigate to opportunity
    cy.login(); // Assumes custom login command is defined
    cy.visit('/opportunities');
    
    // Create or select a won opportunity for testing
    cy.get('[data-test="won-opportunity"]').first().click();
  });

  // ========== TEMPLATE SELECTION FORM TESTS ==========
  
  describe('Template Selection Form', () => {
    it('should display all 5 template cards', () => {
      cy.get('[data-testid="create-project-btn"]').click();
      cy.get('[data-testid="template-card"]').should('have.length', 5);
    });

    it('should display template information correctly', () => {
      cy.get('[data-testid="create-project-btn"]').click();
      
      cy.get('[data-testid="template-card"]').first().within(() => {
        cy.get('[data-testid="template-name"]').should('be.visible');
        cy.get('[data-testid="template-category"]').should('be.visible');
        cy.get('[data-testid="task-count"]').should('contain', 'tasks');
        cy.get('[data-testid="duration"]').should('be.visible');
      });
    });

    it('should have all 5 templates with correct names', () => {
      cy.get('[data-testid="create-project-btn"]').click();
      
      const templateNames = [
        'Web Application',
        'Mobile Application',
        'E-Commerce Platform',
        'System Integration',
        'Maintenance & Support'
      ];

      templateNames.forEach(name => {
        cy.contains('[data-testid="template-name"]', name).should('be.visible');
      });
    });

    it('should have working hover effects on template cards', () => {
      cy.get('[data-testid="create-project-btn"]').click();
      
      cy.get('[data-testid="template-card"]').first().then($card => {
        cy.wrap($card)
          .trigger('mouseenter')
          .should('have.class', 'hover');
      });
    });
  });

  // ========== PREVIEW MODAL TESTS ==========
  
  describe('Template Preview Modal', () => {
    beforeEach(() => {
      cy.get('[data-testid="create-project-btn"]').click();
    });

    it('should open preview modal on button click', () => {
      cy.get('[data-testid="template-card"]').first().within(() => {
        cy.get('[data-testid="preview-btn"]').click();
      });

      cy.get('[data-testid="preview-modal"]').should('be.visible');
    });

    it('should display all tasks in preview modal for Web App template', () => {
      cy.get('[data-testid="template-card"]').first().within(() => {
        cy.get('[data-testid="preview-btn"]').click();
      });

      // Web App template has 8 tasks
      cy.get('[data-testid="task-item"]').should('have.length', 8);
    });

    it('should show task details in preview', () => {
      cy.get('[data-testid="template-card"]').first().within(() => {
        cy.get('[data-testid="preview-btn"]').click();
      });

      cy.get('[data-testid="task-item"]').first().within(() => {
        cy.get('[data-testid="task-name"]').should('be.visible');
        cy.get('[data-testid="task-phase"]').should('be.visible');
        cy.get('[data-testid="task-weight"]').should('be.visible');
        cy.get('[data-testid="task-duration"]').should('be.visible');
      });
    });

    it('should close preview modal', () => {
      cy.get('[data-testid="template-card"]').first().within(() => {
        cy.get('[data-testid="preview-btn"]').click();
      });

      cy.get('[data-testid="preview-modal"]').should('be.visible');
      cy.get('[data-testid="close-modal-btn"]').click();
      cy.get('[data-testid="preview-modal"]').should('not.be.visible');
    });

    it('should load preview modal via AJAX quickly', () => {
      cy.get('[data-testid="template-card"]').first().within(() => {
        cy.get('[data-testid="preview-btn"]').click();
      });

      // Modal should appear within 1 second
      cy.get('[data-testid="preview-modal"]', { timeout: 1000 })
        .should('be.visible');
    });
  });

  // ========== FORM SUBMISSION TESTS ==========
  
  describe('Project Creation Form Submission', () => {
    beforeEach(() => {
      cy.get('[data-testid="create-project-btn"]').click();
    });

    it('should create project with selected template', () => {
      cy.get('[data-testid="template-card"]').first().within(() => {
        cy.get('[data-testid="create-btn"]').click();
      });

      // Should redirect to project detail page
      cy.url().should('include', '/projects/');
      cy.get('[data-testid="task-list"]').should('be.visible');
    });

    it('should create correct number of tasks per template', () => {
      const templateTaskCounts = {
        'Web Application': 8,
        'Mobile Application': 7,
        'E-Commerce Platform': 9,
        'System Integration': 7,
        'Maintenance & Support': 5
      };

      Object.entries(templateTaskCounts).forEach(([templateName, expectedCount]) => {
        cy.visit('/opportunities');
        cy.get('[data-testid="won-opportunity"]').first().click();
        cy.get('[data-testid="create-project-btn"]').click();

        cy.contains('[data-testid="template-name"]', templateName)
          .parent('[data-testid="template-card"]')
          .within(() => {
            cy.get('[data-testid="create-btn"]').click();
          });

        cy.url().should('include', '/projects/');
        cy.get('[data-testid="task-item"]').should('have.length', expectedCount);
      });
    });

    it('should display success message after creation', () => {
      cy.get('[data-testid="template-card"]').first().within(() => {
        cy.get('[data-testid="create-btn"]').click();
      });

      cy.get('[data-testid="success-message"]').should('be.visible');
      cy.get('[data-testid="success-message"]').should('contain', 'created successfully');
    });

    it('should disable button while submitting', () => {
      cy.get('[data-testid="template-card"]').first().within(() => {
        cy.get('[data-testid="create-btn"]').click();
        cy.get('[data-testid="create-btn"]').should('be.disabled');
      });
    });
  });

  // ========== RESPONSIVENESS TESTS ==========
  
  describe('Responsive Design', () => {
    it('should display correctly on mobile (375px)', () => {
      cy.viewport(375, 667);
      cy.get('[data-testid="create-project-btn"]').click();
      
      cy.get('[data-testid="template-card"]').should('be.visible');
      cy.get('[data-testid="template-card"]').should('have.css', 'width');
    });

    it('should display correctly on tablet (768px)', () => {
      cy.viewport(768, 1024);
      cy.get('[data-testid="create-project-btn"]').click();
      
      cy.get('[data-testid="template-card"]').should('be.visible');
    });

    it('should display correctly on desktop (1920px)', () => {
      cy.viewport(1920, 1080);
      cy.get('[data-testid="create-project-btn"]').click();
      
      cy.get('[data-testid="template-card"]').should('be.visible');
    });
  });

  // ========== ERROR HANDLING TESTS ==========
  
  describe('Error Handling', () => {
    it('should handle network errors gracefully', () => {
      cy.intercept('POST', '/api/opportunities/*/projects/with-template', {
        statusCode: 500,
        body: { success: false, error: 'Server error' }
      });

      cy.get('[data-testid="create-project-btn"]').click();
      cy.get('[data-testid="template-card"]').first().within(() => {
        cy.get('[data-testid="create-btn"]').click();
      });

      cy.get('[data-testid="error-message"]').should('be.visible');
    });

    it('should show no console errors', () => {
      cy.get('[data-testid="create-project-btn"]').click();
      
      // Check for any console errors
      cy.on('window:before:load', (win) => {
        cy.spy(win.console, 'error');
      });

      cy.get('[data-testid="template-card"]').first().within(() => {
        cy.get('[data-testid="preview-btn"]').click();
      });

      cy.window().then((win) => {
        expect(win.console.error).not.to.have.been.called;
      });
    });
  });

  // ========== ACCESSIBILITY TESTS ==========
  
  describe('Accessibility', () => {
    it('should have proper ARIA labels', () => {
      cy.get('[data-testid="create-project-btn"]').click();
      
      cy.get('[data-testid="template-card"]').each($card => {
        cy.wrap($card).should('have.attr', 'role');
      });
    });

    it('should be keyboard navigable', () => {
      cy.get('[data-testid="create-project-btn"]').click();
      
      cy.get('[data-testid="template-card"]').first().within(() => {
        cy.get('[data-testid="preview-btn"]').focus();
        cy.get('[data-testid="preview-btn"]').should('have.focus');
      });
    });
  });
});

// ========== ADMIN TEMPLATE DASHBOARD TESTS ==========

describe('Admin Template Dashboard', () => {
  beforeEach(() => {
    cy.loginAsAdmin();
    cy.visit('/admin/templates');
  });

  it('should display all 5 templates in admin table', () => {
    cy.get('[data-testid="template-row"]').should('have.length', 5);
  });

  it('should have working create template button', () => {
    cy.get('[data-testid="create-template-btn"]').click();
    cy.get('[data-testid="create-template-modal"]').should('be.visible');
  });

  it('should create new template', () => {
    cy.get('[data-testid="create-template-btn"]').click();
    
    cy.get('[data-testid="create-template-modal"]').within(() => {
      cy.get('[data-testid="template-name"]').type('Custom Template');
      cy.get('[data-testid="template-category"]').select('Custom');
      cy.get('[data-testid="save-btn"]').click();
    });

    cy.get('[data-testid="success-message"]').should('be.visible');
  });

  it('should manage template tasks', () => {
    cy.get('[data-testid="template-row"]').first().within(() => {
      cy.get('[data-testid="tasks-btn"]').click();
    });

    cy.get('[data-testid="task-list"]').should('be.visible');
    cy.get('[data-testid="task-item"]').should('have.length.greaterThan', 0);
  });

  it('should delete template with confirmation', () => {
    cy.get('[data-testid="template-row"]').last().within(() => {
      cy.get('[data-testid="delete-btn"]').click();
    });

    cy.get('[data-testid="confirm-dialog"]').should('be.visible');
    cy.get('[data-testid="confirm-delete-btn"]').click();

    cy.get('[data-testid="success-message"]').should('be.visible');
  });
});
