# User Feature Testing Guide - Phase 5.4.5

**Document Version:** 1.0  
**Date:** February 27, 2026  
**Scope:** End-user feature validation and acceptance testing  
**Audience:** Project Managers, Business Analysts, End Users, UAT Team  

---

## 📋 TABLE OF CONTENTS

1. [Introduction](#introduction)
2. [Feature Overview](#feature-overview)
3. [User Scenarios](#user-scenarios)
4. [Step-by-Step Testing](#step-by-step-testing)
5. [Expected Behaviors](#expected-behaviors)
6. [Acceptance Criteria](#acceptance-criteria)
7. [Issues & Reporting](#issues--reporting)
8. [Sign-Off Checklist](#sign-off-checklist)

---

## 👋 INTRODUCTION

### Purpose
This guide helps you test and validate that the **Project Template Selection Feature** works correctly from a user's perspective. You don't need to be technical - just follow the steps and verify the expected outcomes.

### What is Being Tested?
When creating a new project opportunity, users can now:
- Select from 5 pre-built project templates
- Preview template details before selecting
- Automatically generate work tasks based on the selected template
- Track project phases and work distribution

### Who Should Test?
- Project Managers creating new opportunities
- Business Analysts validating requirements
- End users who will use the feature daily
- Quality Assurance team members

### Time Required
- **Quick Test** (15 minutes): Basic feature walkthrough
- **Complete Test** (1 hour): All scenarios and edge cases
- **Comprehensive Test** (2 hours): Full user acceptance testing

---

## 🎯 FEATURE OVERVIEW

### The 5 Available Templates

#### 1. **Web Application** 
- **Best for:** Building web-based software products
- **Duration:** ~90 days
- **Work Phases:** 8 planning, development, testing, deployment phases
- **Example:** Customer portal, SaaS platform, internal tool

#### 2. **Mobile Application**
- **Best for:** iOS/Android mobile apps
- **Duration:** ~75 days
- **Work Phases:** 7 design, development, testing, app store release phases
- **Example:** Mobile app for iOS/Android platforms

#### 3. **E-Commerce Platform**
- **Best for:** Online stores and shopping experiences
- **Duration:** ~100 days
- **Work Phases:** 9 planning, product catalog, payment, deployment phases
- **Example:** Online store, marketplace, subscription platform

#### 4. **System Integration**
- **Best for:** Connecting different business systems
- **Duration:** ~85 days
- **Work Phases:** 7 analysis, development, testing, deployment phases
- **Example:** ERP integration, API connection, data migration

#### 5. **Maintenance & Support**
- **Best for:** Ongoing maintenance and bug fixes
- **Duration:** ~30 days
- **Work Phases:** 5 assessment, fixes, testing, documentation phases
- **Example:** System updates, performance optimization, security patches

---

## 👥 USER SCENARIOS

### Scenario 1: Project Manager Creates New Web App Project
**User:** Sarah (Project Manager)  
**Goal:** Create a new web application project and generate work plan  
**Expected Time:** 10 minutes

### Scenario 2: Business Analyst Reviews Template Details
**User:** John (Business Analyst)  
**Goal:** Preview templates before committing to template selection  
**Expected Time:** 15 minutes

### Scenario 3: Team Lead Applies Template to Existing Project
**User:** Maria (Team Lead)  
**Goal:** Apply a template to a project that was created without one  
**Expected Time:** 5 minutes

### Scenario 4: Admin Manages Custom Templates
**User:** David (System Admin)  
**Goal:** View all templates and edit template details  
**Expected Time:** 20 minutes

---

## 🔍 STEP-BY-STEP TESTING

### TEST 1: Access Template Selection Feature

**Steps:**
1. Open the application in your browser
2. Navigate to "Create New Opportunity" (or similar section)
3. Fill in basic opportunity details:
   - Opportunity Name: "Test Web App Project"
   - Opportunity Status: "Proposal"
   - Expected Value: "$50,000"
   - Client: "Test Client"

**Expected Outcomes:**
✓ Form loads without errors  
✓ All fields are visible and editable  
✓ No JavaScript errors in browser console  
✓ Form is responsive on your device  

**If Something Goes Wrong:**
- Check browser console for errors (F12 → Console tab)
- Try refreshing the page
- Clear browser cache
- Try different browser

**Screenshot Checkpoint:**
Take a screenshot showing the opportunity form you filled in.

---

### TEST 2: View Template Selection Cards

**Steps:**
1. Continue from Test 1 or open the template selection page
2. Look for the "Select Project Template" section
3. Observe the 5 template cards displayed:
   - Web Application
   - Mobile Application
   - E-Commerce Platform
   - System Integration
   - Maintenance & Support

**Expected Outcomes:**
✓ All 5 template cards are visible  
✓ Each card shows:
  - Template name (clearly readable)
  - Template description (what it's for)
  - Number of tasks in the template
  - Estimated duration
  - A preview button
✓ Cards are well-organized and easy to read  
✓ Cards have consistent styling  
✓ Cards are responsive on mobile/tablet/desktop  

**Validation:**
For each template card, verify:
- [ ] Card title is visible
- [ ] Description is readable
- [ ] Task count is shown (e.g., "8 tasks", "7 tasks")
- [ ] Duration estimate is shown
- [ ] Preview button is present
- [ ] Card is clickable

**Screenshot Checkpoint:**
Take a screenshot showing all 5 template cards.

---

### TEST 3: Preview Template Details

**Steps:**
1. Click the "Preview" button on any template card (try "Web Application" first)
2. Observe the preview modal/popup that appears
3. Review the information shown:
   - Template name and description
   - List of all tasks in the template
   - Each task's name, phase number, and weight
   - Total number of tasks
   - Total duration estimate

**Expected Outcomes:**
✓ Preview modal opens smoothly  
✓ No JavaScript errors  
✓ Modal displays:
  - Template name clearly
  - Full description
  - All tasks listed (for Web App: 8 tasks)
  - Each task shows:
    - Task name (e.g., "Requirements & Design")
    - Phase number (1, 2, 3, etc.)
    - Work percentage/weight
    - Estimated days to complete
✓ Modal is readable and well-formatted  
✓ Modal closes properly when you click "Close"  

**Task List Validation** (Web Application Example):
- [ ] Phase 1: Requirements & Design - visible
- [ ] Phase 2: Architecture & Planning - visible
- [ ] Phase 3: Development - visible
- [ ] Phase 4: API Development - visible
- [ ] Phase 5: Testing & QA - visible
- [ ] Phase 6: Optimization - visible
- [ ] Phase 7: Deployment Preparation - visible
- [ ] Phase 8: Production Deployment - visible

**Screenshot Checkpoint:**
Take screenshots of:
1. Preview modal open (showing tasks)
2. Different templates' previews (try 2-3 different templates)

---

### TEST 4: Select a Template

**Steps:**
1. Return to the template selection cards (close any preview modal)
2. Click on one of the template cards (try "Mobile Application")
3. Verify the card is now selected/highlighted
4. Observe any indication that the template is selected
5. Look for a "Confirm" or "Next" button
6. Click the confirmation button to proceed with this template

**Expected Outcomes:**
✓ Card highlights or shows selected state when clicked  
✓ Selection is visually clear (e.g., blue border, checkmark)  
✓ Selected template name appears somewhere on the page  
✓ Confirmation button becomes enabled  
✓ Clicking confirmation button progresses the workflow  
✓ Page indicates "Creating project with [Template Name]..."  
✓ No errors occur during submission  

**Validation Checklist:**
- [ ] Template card shows selected state
- [ ] Only one template is selected at a time
- [ ] Button text is clear ("Create Project", "Confirm", etc.)
- [ ] Loading indicator appears during processing
- [ ] Redirect to project happens smoothly

**Screenshot Checkpoint:**
Take screenshot of:
1. Selected template card (showing highlight/selection)
2. Confirmation dialog or button
3. Loading state

---

### TEST 5: Verify Project Creation with Template

**Steps:**
1. After confirming template selection, wait for page to load
2. Navigate to the newly created project
3. Open the "Tasks" or "Work Items" section
4. Count the tasks created and verify they match the template

**Expected Outcomes:**
✓ Project is created successfully  
✓ Project shows the correct template name in details  
✓ Tasks section displays all expected tasks:
  - Mobile App template should have: 7 tasks
  - Each task is visible with:
    - Task name
    - Phase/sequence number
    - Status (new, "To Do", etc.)
    - Work percentage assigned
✓ No duplicate tasks  
✓ Tasks are in correct order  
✓ All task details are accurate  

**Task Count Verification:**
- [ ] Web Application → 8 tasks created
- [ ] Mobile Application → 7 tasks created
- [ ] E-Commerce Platform → 9 tasks created
- [ ] System Integration → 7 tasks created
- [ ] Maintenance & Support → 5 tasks created

**Screenshot Checkpoint:**
Take screenshot of:
1. Project details showing template used
2. Tasks list showing all created tasks
3. Individual task details

---

### TEST 6: Preview Modal Responsiveness

**Steps:**
1. Return to template selection page
2. Open preview modal for a template
3. Test on different screen sizes:
   - **Smartphone** (375 × 667): Use DevTools or actual phone
   - **Tablet** (768 × 1024): Use DevTools or actual tablet
   - **Desktop** (1920 × 1080): Regular desktop view

**Expected Outcomes on Each Device:**
✓ Modal is readable and usable  
✓ Text is not cut off  
✓ Buttons are clickable (not too small)  
✓ Task list scrolls if needed  
✓ No horizontal scrolling required  
✓ Layout adapts to screen size  
✓ Images/icons are visible  

**Mobile Specific Checks:**
- [ ] Text is large enough to read
- [ ] Buttons are spaced for touch (not crammed)
- [ ] Modal doesn't cover entire screen (can close easily)
- [ ] Scrolling works smoothly

**Tablet Specific Checks:**
- [ ] Layout looks balanced
- [ ] Task list uses space efficiently
- [ ] Modal is appropriately sized
- [ ] No wasted whitespace

**Desktop Specific Checks:**
- [ ] Modal is centered on screen
- [ ] Optimal reading width
- [ ] All information visible without scrolling when possible
- [ ] Hover effects work (if applicable)

**Screenshot Checkpoint:**
Take screenshots at 3 different screen sizes showing:
1. Template selection cards
2. Preview modal
3. Full page layout

---

### TEST 7: Form Validation

**Steps:**
1. Go back to opportunity creation form
2. Try selecting a template WITHOUT filling in required fields
3. Try submitting with invalid data:
   - Empty opportunity name
   - Invalid monetary value
   - Missing client information
4. Observe error messages

**Expected Outcomes:**
✓ Form shows clear error messages  
✓ Error messages indicate which field(s) are invalid  
✓ User cannot submit with incomplete information  
✓ Red highlighting on invalid fields  
✓ Error messages are helpful and specific:
  - ✗ BAD: "Invalid input"
  - ✓ GOOD: "Please enter an opportunity name (2-100 characters)"
✓ User can fix errors and resubmit  

**Validation Scenarios to Test:**
- [ ] Missing opportunity name
- [ ] Invalid monetary value (letters instead of numbers)
- [ ] Opportunity name too short (< 2 characters)
- [ ] Opportunity name too long (> 100 characters)
- [ ] Missing required fields (as per your form)

**Screenshot Checkpoint:**
Take screenshots of error messages for at least 2 scenarios.

---

### TEST 8: Template Switching & Changes

**Steps:**
1. Create a project with one template (e.g., Web Application)
2. Navigate to the project's template settings (if available)
3. Try to switch to a different template (e.g., Mobile Application)
4. Observe what happens:
   - Does it warn about overwriting tasks?
   - Does it add tasks or replace them?
   - Are existing tasks preserved?

**Expected Outcomes:**
✓ System prevents adding template to already-templated project  
✓ If switching is allowed:
  - ✓ Confirmation dialog appears
  - ✓ User is warned about potential changes
  - ✓ User must explicitly confirm
  - ✓ Old tasks are handled appropriately
    - Either preserved and new tasks added, OR
    - Clear that old tasks will be replaced
✓ Switching is transparent and safe  
✓ No data loss without user awareness  

**Screenshot Checkpoint:**
Take screenshots of:
1. Attempt to switch template
2. Confirmation dialog (if shown)
3. Before and after task lists

---

### TEST 9: Admin Template Management (If Available)

**Steps:**
1. Log in as an admin user (if applicable)
2. Navigate to template management section
3. View all templates in admin interface
4. Try to view/edit individual templates:
   - Click on "Web Application" template
   - Observe editable fields
   - Note: Don't make changes yet
5. Review template task list in admin interface
6. Check if tasks can be reordered or edited (without saving)

**Expected Outcomes:**
✓ Admin can view all 5 templates in a list  
✓ Admin interface shows:
  - Template name
  - Description
  - Number of tasks
  - Is Active status
  - Last modified date
✓ Clicking template opens detail view  
✓ Detail view shows:
  - All editable fields (name, description)
  - Complete task list
  - Option to add/remove tasks (if implemented)
  - Edit and Save/Cancel buttons
✓ Buttons are clearly labeled and functional  
✓ Changes are NOT saved (since we're just validating)  

**Screenshot Checkpoint:**
Take screenshots of:
1. Admin template list
2. Individual template detail page
3. Task list in admin interface

---

### TEST 10: Performance & Load Times

**Steps:**
1. Open template selection page
2. Note the time it takes to:
   - Load the page (should show templates)
   - Open a preview modal (should show task list)
   - Submit template selection (should redirect to project)
3. Use browser DevTools to measure if needed:
   - Open DevTools (F12)
   - Go to Network tab
   - Refresh page and observe load times
4. Try on slower internet (if possible):
   - Use DevTools to throttle speed
   - Settings → Network conditions → Slow 3G

**Expected Outcomes:**
✓ Page loads in < 3 seconds  
✓ Preview modal opens in < 1 second  
✓ Form submission completes in < 5 seconds  
✓ No timeouts or slow loading  
✓ Progress indicators show during loading  
✓ Page functions smoothly even on slow connections  

**Performance Targets:**
- Page load: ✓ < 3 seconds
- Preview modal: ✓ < 1 second
- Template selection submit: ✓ < 5 seconds
- Task list display: ✓ < 2 seconds

**Screenshot Checkpoint:**
Take screenshots of:
1. Network tab showing load times
2. Performance metrics (if available)

---

## ✅ EXPECTED BEHAVIORS

### When Everything Works Correctly

#### Template Selection Flow:
```
1. User fills opportunity form → ✓ No errors
2. User sees 5 template cards → ✓ All visible, properly formatted
3. User clicks Preview → ✓ Modal opens with task list
4. User clicks Select → ✓ Card highlights as selected
5. User clicks Confirm → ✓ Project is created with tasks
6. User sees project page → ✓ All tasks from template present
```

#### Expected Visual Elements:
```
✓ Template Card Should Show:
  - Template name (bold, large text)
  - Subtitle/description (reads like a benefit)
  - Icon or color badge for category
  - "8 tasks" or task count
  - "90 days" or duration
  - "Preview" button

✓ Preview Modal Should Show:
  - Template name (at top)
  - Full description
  - Task table or list with columns:
    * Phase number
    * Task name
    * Weight/percentage
    * Estimated days
  - "Close" button

✓ Project Created Should Show:
  - Template name in project details
  - Task list with all tasks from template
  - Each task with:
    * Phase number
    * Task name
    * Status indicator
    * Work percentage assigned
```

#### User Experience Quality:
```
✓ Intuitive - User understands what to do
✓ Responsive - Buttons react immediately to clicks
✓ Clear - No confusing messages or unclear states
✓ Forgiving - Easy to go back and change selections
✓ Informative - User knows what's happening
✓ Pleasant - Colors, fonts, spacing look good
```

---

## 📋 ACCEPTANCE CRITERIA

### Must Have ✓ (Critical)
- [ ] All 5 template cards display correctly
- [ ] Template preview works for all 5 templates
- [ ] Templates can be selected and confirmed
- [ ] Projects are created with correct task count
- [ ] Tasks appear on project page with correct names
- [ ] Feature works on desktop, tablet, and mobile
- [ ] No JavaScript errors in browser console
- [ ] Forms validate input correctly
- [ ] Page loads in reasonable time (< 3 seconds)

### Should Have ✓ (Important)
- [ ] Template descriptions are helpful and clear
- [ ] Task weights add up correctly (100% per template)
- [ ] Template selection flow is intuitive
- [ ] Error messages are clear and helpful
- [ ] Admin can view and edit templates
- [ ] Performance is acceptable on slow networks
- [ ] Responsive design looks good at all sizes
- [ ] Hover effects/visual feedback present

### Nice to Have ✓ (Enhancement)
- [ ] Animations when cards load
- [ ] Search/filter for templates
- [ ] Recent templates recommendations
- [ ] Template usage statistics
- [ ] Custom template creation (if planned)
- [ ] Template comparison view
- [ ] Keyboard navigation support
- [ ] Accessibility features (screen reader support)

---

## 🐛 ISSUES & REPORTING

### How to Report an Issue

**When you find a problem:**

1. **Stop and Note Details:**
   - What were you doing?
   - What happened?
   - What should have happened?
   - Which device/browser?

2. **Take Evidence:**
   - Screenshot or screen recording
   - Note the URL/page
   - Copy any error messages
   - Note exact steps to reproduce

3. **Fill Out Report Form:**

```
FEATURE TEST ISSUE REPORT

Issue #: [Auto-assigned]
Date Found: [Today's date]
Tested By: [Your name]
Device: [Desktop/Tablet/Mobile - model if known]
Browser: [Chrome/Safari/Firefox/Edge - version]
OS: [Windows/Mac/iOS/Android - version]

ISSUE TITLE:
[Brief description of the problem]

STEP-BY-STEP REPRODUCTION:
1. [First action]
2. [Second action]
3. [What I expected]
4. [What actually happened]

EXPECTED BEHAVIOR:
[What should have happened]

ACTUAL BEHAVIOR:
[What actually happened]

EVIDENCE:
- Screenshot: [Attached or described]
- URL: [Page where issue occurred]
- Error message: [If any, copy exact text]

SEVERITY:
☐ Critical (Cannot use feature at all)
☐ High (Feature partially broken or very difficult to use)
☐ Medium (Minor functionality issue)
☐ Low (Cosmetic issue, doesn't affect use)

NOTES:
[Any additional context]
```

### Severity Levels

**Critical (🔴 Red):**
- Feature doesn't work at all
- Feature causes system error
- Feature loses user data
- **Example:** "Template cards don't load" or "Project not created after template selection"

**High (🟠 Orange):**
- Feature partially broken
- Feature is confusing or unintuitive
- Performance is very poor
- **Example:** "Preview modal fails to load" or "Form doesn't validate input"

**Medium (🟡 Yellow):**
- Minor functionality issue
- Workaround exists
- Affects specific scenario
- **Example:** "Task count shows incorrectly" or "Button text is unclear"

**Low (🟢 Green):**
- Cosmetic/minor issue
- No impact on functionality
- Nice to fix but not urgent
- **Example:** "Spacing between cards is uneven" or "Font size could be larger"

---

## 📝 SIGN-OFF CHECKLIST

### For Each Test Session, Complete This:

**Tester Information:**
- [ ] Tester Name: ________________
- [ ] Date Tested: ________________
- [ ] Device(s) Used: ☐ Desktop ☐ Tablet ☐ Mobile ☐ Multiple
- [ ] Browser(s) Used: ________________
- [ ] Duration: ________________ minutes

**Feature Tests Completed:**
- [ ] Test 1: Access Template Selection
- [ ] Test 2: View Template Cards
- [ ] Test 3: Preview Templates
- [ ] Test 4: Select Template
- [ ] Test 5: Verify Project Creation
- [ ] Test 6: Test Responsiveness
- [ ] Test 7: Form Validation
- [ ] Test 8: Template Switching
- [ ] Test 9: Admin Management (if applicable)
- [ ] Test 10: Performance & Load Times

**Results Summary:**
- [ ] All Tests Passed: YES / NO
- [ ] Issues Found: [Number of issues]
  - Critical: ___
  - High: ___
  - Medium: ___
  - Low: ___
- [ ] Critical Issues: ☐ NONE ☐ Found (must fix before launch)

**Overall Assessment:**
```
Feature Status:
☐ READY FOR PRODUCTION - All tests passed, no critical issues
☐ READY WITH FIXES - Minor issues found and fixed
☐ NOT READY - Critical issues require rework
☐ NEEDS RETESTING - Changes made, needs revalidation
```

**Tester Sign-Off:**
- Tester Signature: ________________
- Date: ________________
- Comments: 

```
[Any additional notes about testing experience,
issues found, patterns noticed, or recommendations]
```

---

## 🎯 QUICK REFERENCE

### For Quick Testing (15 minutes):
1. Load template selection page
2. View all 5 template cards
3. Preview one template
4. Select and create a project
5. Verify tasks were created

### For Complete Testing (1 hour):
1. Run ALL 10 Test procedures
2. Test on desktop, tablet, and mobile
3. Report any issues found
4. Verify UI is intuitive
5. Check performance

### For UAT Testing (2 hours):
1. Complete all standard tests
2. Test with real business scenarios
3. Get feedback from actual end users
4. Document any workflow issues
5. Validate business requirements

---

## 📞 QUESTIONS & SUPPORT

### Common Questions

**Q: What if I don't have admin access?**  
A: Skip Test 9. Report to your testing lead if needed.

**Q: What if the feature isn't available yet?**  
A: Contact your project manager. The feature may not be deployed.

**Q: Should I save my test data?**  
A: No - testing usually uses sandbox/test data. Any changes you make will be for testing only.

**Q: What if I find a bug?**  
A: Follow the "Issues & Reporting" section to document it properly.

**Q: How do I test on mobile?**  
A: Use DevTools (F12 → toggle device toolbar) or a real mobile device.

**Q: What browser should I use?**  
A: Test on Chrome (most common) and 1-2 others (Safari, Firefox, Edge).

---

## 📊 TESTING COMPLETION SUMMARY

After completing all tests, you should have documented:
- ✅ Number of tests run: 10
- ✅ Tests passed: ___ / 10
- ✅ Tests failed: ___ / 10
- ✅ Issues found: ___ total
  - Critical: ___ (must fix)
  - High: ___ (should fix)
  - Medium: ___ (nice to fix)
  - Low: ___ (cosmetic)
- ✅ Device coverage: Desktop / Tablet / Mobile
- ✅ Browser coverage: ___ browsers tested
- ✅ Overall status: PASS / FAIL / NEEDS FIXES

---

## 🎊 YOU'RE DONE!

Once you've completed this guide:
1. ✅ Submit your sign-off form
2. ✅ Share any issues found with your team lead
3. ✅ Celebrate - you've helped validate a critical feature! 🎉

**Thank you for testing!**

---

*Last Updated: February 27, 2026*  
*Document Owner: Product Manager*  
*Next Review: After Phase 5.4.5 completion*
