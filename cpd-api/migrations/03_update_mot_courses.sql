-- 03_update_mot_courses.sql
-- Lock MOT templates and add training content

-- Update existing MOT templates to be locked and add content
UPDATE courses 
SET 
    is_locked = TRUE,
    content = '<h2>MOT Class 1 & 2 Training Overview</h2>
<p>This comprehensive training course covers the annual requirements for MOT Class 1 and 2 testers (Motorcycles).</p>

<h3>Module 1: Legislative Updates</h3>
<p>Review of current DVSA regulations and recent changes to motorcycle testing standards.</p>
<ul>
  <li>Road Traffic Act updates</li>
  <li>New emissions standards</li>
  <li>Safety equipment requirements</li>
</ul>

<h3>Module 2: Technical Inspection</h3>
<p>Detailed examination procedures for motorcycle systems:</p>
<ul>
  <li>Brake system inspection and testing</li>
  <li>Steering and suspension checks</li>
  <li>Lighting and electrical systems</li>
  <li>Exhaust and emissions testing</li>
</ul>

<h3>Module 3: Common Defects</h3>
<p>Identifying and categorizing motorcycle defects:</p>
<ul>
  <li>Major vs. minor defects</li>
  <li>Dangerous defects requiring immediate attention</li>
  <li>Advisory items for customer awareness</li>
</ul>

<h3>Assessment</h3>
<p>Complete the end-of-course quiz to demonstrate your understanding. You must achieve 80% or higher to pass.</p>'
WHERE title = 'MOT Class 1 & 2 Training';

UPDATE courses 
SET 
    is_locked = TRUE,
    content = '<h2>MOT Class 4 & 7 Training Overview</h2>
<p>This comprehensive training course covers the annual requirements for MOT Class 4 and 7 testers (Cars and light commercial vehicles).</p>

<h3>Module 1: Regulatory Framework</h3>
<p>Understanding current DVSA requirements for passenger cars and light vans:</p>
<ul>
  <li>MOT testing standards and procedures</li>
  <li>Legislative updates and compliance</li>
  <li>Record keeping and documentation</li>
</ul>

<h3>Module 2: Vehicle Systems Inspection</h3>
<p>Comprehensive testing procedures:</p>
<ul>
  <li>Braking systems - efficiency and condition</li>
  <li>Steering and suspension geometry</li>
  <li>Lights, reflectors, and electrical systems</li>
  <li>Tyres and wheels - tread depth and condition</li>
  <li>Exhaust emissions testing</li>
  <li>Body structure and corrosion</li>
</ul>

<h3>Module 3: Defect Classification</h3>
<p>Properly identifying and recording vehicle defects:</p>
<ul>
  <li>Dangerous defects - immediate failure</li>
  <li>Major defects - MOT failure</li>
  <li>Minor defects - advisory notice</li>
  <li>Documentation and customer communication</li>
</ul>

<h3>Module 4: Special Procedures</h3>
<p>Advanced testing scenarios:</p>
<ul>
  <li>Diesel particulate filter (DPF) inspection</li>
  <li>Electronic stability control (ESC) testing</li>
  <li>Advanced Driver Assistance Systems (ADAS)</li>
</ul>

<h3>Assessment</h3>
<p>Complete the end-of-course assessment to validate your knowledge. A score of 80% or higher is required to pass.</p>'
WHERE title = 'MOT Class 4 & 7 Training';

UPDATE courses 
SET 
    is_locked = TRUE,
    content = '<h2>MOT Tester Annual Assessment</h2>
<p>This mandatory assessment validates your continued competence as an MOT tester.</p>

<h3>Assessment Instructions</h3>
<p>This assessment covers all aspects of MOT testing that you have completed training for. You will be tested on:</p>
<ul>
  <li>Legislative knowledge and compliance</li>
  <li>Technical inspection procedures</li>
  <li>Defect identification and classification</li>
  <li>Safety and best practices</li>
</ul>

<h3>Requirements</h3>
<ul>
  <li>Time limit: 60 minutes</li>
  <li>Passing score: 80%</li>
  <li>You may retake the assessment if needed</li>
  <li>Results are recorded for DVSA compliance</li>
</ul>

<h3>Begin Assessment</h3>
<p>When you are ready, proceed to the quiz section to begin your annual assessment.</p>'
WHERE title = 'MOT Tester Annual Assessment';
