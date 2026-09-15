#!/usr/bin/env python3
"""
KP Fisheries E-License System (RFLMS)
Admin User Manual PDF Generator (Optimized Compact-TOC Dual-Pass Layout)
"""

import os
import sys
import re
import base64
import subprocess

def get_base64_image(image_path):
    if os.path.exists(image_path):
        with open(image_path, "rb") as img_file:
            return "data:image/png;base64," + base64.b64encode(img_file.read()).decode("utf-8")
    return ""

def build_manual_html(toc_pages=None):
    logo_b64 = get_base64_image("/var/www/kp-fisheries-e-license/public/images/logo-2.png")
    
    if toc_pages is None:
        toc_pages = {k: "..." for k in [
            "sec1", "sec1_1", "sec1_2", "sec1_3", "sec1_4", "sec1_5",
            "sec2", "sec2_1", "sec2_2", "sec2_3", "sec2_4", "sec2_5", "sec2_6", "sec2_7", "sec2_8", "sec2_9", "sec2_10", "sec2_11", "sec2_12",
            "sec3", "sec3_1", "sec3_2", "sec3_3", "sec3_4", "sec3_5", "sec3_6", "sec3_7", "sec3_8", "sec3_9", "sec3_10", "sec3_11", "sec3_12",
            "sec4", "sec4_1", "sec4_2", "sec4_3", "sec4_4",
            "sec5"
        ]}
    
    html = f"""<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>KP Fisheries RFLMS - Administrator User Manual</title>
<style>
  @page {{
    size: A4;
    margin: 15mm 14mm 15mm 14mm;
    @bottom-right {{
      content: counter(page);
      font-size: 8pt;
      color: #64748b;
    }}
  }}

  * {{
    box-sizing: border-box;
    margin: 0;
    padding: 0;
  }}

  body {{
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    color: #1e293b;
    background: #ffffff;
    font-size: 9pt;
    line-height: 1.45;
  }}

  /* Page Break Helpers */
  .page-break-before {{
    page-break-before: always;
    break-before: page;
  }}
  .page-break-after {{
    page-break-after: always;
    break-after: page;
  }}
  .avoid-break {{
    page-break-inside: avoid;
    break-inside: avoid;
  }}

  /* Typography */
  h1, h2, h3, h4, h5, h6 {{
    color: #0f172a;
    font-weight: 700;
    line-height: 1.25;
    page-break-after: avoid;
    break-after: avoid;
  }}
  h1 {{ font-size: 18pt; margin-bottom: 7pt; }}
  h2 {{ 
    font-size: 13pt; 
    margin-top: 13pt; 
    margin-bottom: 6pt; 
    padding-bottom: 3pt; 
    border-bottom: 2px solid #0d5c3a; 
    color: #0d5c3a;
  }}
  h3 {{ font-size: 10.5pt; margin-top: 9pt; margin-bottom: 4pt; color: #1e3a2b; }}
  h4 {{ font-size: 9.5pt; margin-top: 6pt; margin-bottom: 3pt; color: #334155; }}
  p {{ margin-bottom: 5pt; text-align: justify; }}

  /* Cover Page */
  .cover-container {{
    border: 3px double #0d5c3a;
    padding: 18mm 14mm 14mm 14mm;
    text-align: center;
    background: linear-gradient(180deg, #f8faf9 0%, #ffffff 100%);
    page-break-after: always;
    break-after: page;
  }}
  .cover-header img {{
    height: 80px;
    margin-bottom: 8pt;
  }}
  .cover-dept {{
    font-size: 13pt;
    font-weight: 700;
    color: #0d5c3a;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    margin-bottom: 3pt;
  }}
  .cover-subdept {{
    font-size: 10pt;
    font-weight: 600;
    color: #475569;
    margin-bottom: 14pt;
  }}
  .cover-title-box {{
    background: #0d5c3a;
    color: #ffffff;
    padding: 16pt 12pt;
    border-radius: 6px;
    margin: 14pt 0;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  }}
  .cover-title-box h1 {{
    color: #ffffff;
    font-size: 20pt;
    margin-bottom: 5pt;
    letter-spacing: 0.5px;
  }}
  .cover-title-box .subtitle {{
    font-size: 11pt;
    color: #e2e8f0;
    font-weight: 500;
  }}
  .cover-desc {{
    font-size: 9pt;
    color: #334155;
    text-align: center;
    max-width: 85%;
    margin: 12pt auto;
    line-height: 1.5;
  }}
  .cover-metadata {{
    margin: 14pt auto 10pt auto;
    display: inline-block;
    text-align: left;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 8pt 14pt;
    font-size: 8.5pt;
  }}
  .cover-metadata table {{
    border-collapse: collapse;
  }}
  .cover-metadata td {{
    padding: 2pt 5pt;
  }}
  .cover-metadata td.label {{
    font-weight: 700;
    color: #0d5c3a;
  }}
  .cover-footer {{
    font-size: 7.5pt;
    color: #64748b;
    border-top: 1px solid #cbd5e1;
    padding-top: 6pt;
    margin-top: 10pt;
  }}

  /* Table of Contents */
  .toc-container {{
    page-break-after: always;
    break-after: page;
  }}
  .toc-title {{
    font-size: 13pt;
    color: #0d5c3a;
    border-bottom: 2px solid #0d5c3a;
    padding-bottom: 2pt;
    margin-bottom: 6pt;
  }}
  .toc-item {{
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    padding: 1.5pt 0;
    font-size: 8pt;
    border-bottom: 1px dotted #e2e8f0;
  }}
  .toc-item.level-1 {{
    font-weight: 700;
    color: #0f172a;
    margin-top: 4pt;
    font-size: 8.5pt;
  }}
  .toc-item.level-2 {{
    padding-left: 10pt;
    color: #334155;
  }}
  .toc-dots {{
    flex-grow: 1;
    border-bottom: 1px dotted #94a3b8;
    margin: 0 4pt;
  }}

  /* Callout Boxes */
  .callout {{
    padding: 6pt 9pt;
    border-radius: 4px;
    margin: 6pt 0;
    font-size: 8.5pt;
    page-break-inside: avoid;
    break-inside: avoid;
  }}
  .callout-info {{
    background-color: #eff6ff;
    border-left: 4px solid #2563eb;
    color: #1e3a8a;
  }}
  .callout-warning {{
    background-color: #fffbeb;
    border-left: 4px solid #d97706;
    color: #92400e;
  }}
  .callout-danger {{
    background-color: #fef2f2;
    border-left: 4px solid #dc2626;
    color: #991b1b;
  }}
  .callout-success {{
    background-color: #f0fdf4;
    border-left: 4px solid #16a34a;
    color: #166534;
  }}
  .callout strong {{
    display: block;
    margin-bottom: 1.5pt;
    font-size: 9pt;
  }}

  /* Step Boxes */
  .step-box {{
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-left: 4px solid #0d5c3a;
    border-radius: 4px;
    padding: 7pt 9pt;
    margin: 6pt 0;
    page-break-inside: avoid;
    break-inside: avoid;
  }}
  .step-number {{
    display: inline-block;
    background: #0d5c3a;
    color: #ffffff;
    font-size: 7.5pt;
    font-weight: bold;
    padding: 1pt 5pt;
    border-radius: 3px;
    margin-bottom: 2pt;
  }}
  .step-title {{
    font-weight: 700;
    font-size: 9.5pt;
    color: #0f172a;
    margin-bottom: 2pt;
  }}

  /* Tables */
  table.data-table {{
    width: 100%;
    border-collapse: collapse;
    margin: 6pt 0;
    font-size: 8pt;
    page-break-inside: avoid;
    break-inside: avoid;
  }}
  table.data-table th {{
    background-color: #0d5c3a;
    color: #ffffff;
    font-weight: 600;
    text-align: left;
    padding: 3.5pt 5pt;
    border: 1px solid #0d5c3a;
  }}
  table.data-table td {{
    padding: 3.5pt 5pt;
    border: 1px solid #cbd5e1;
    vertical-align: top;
  }}
  table.data-table tr:nth-child(even) td {{
    background-color: #f8fafc;
  }}

  /* Badges & Tags */
  .badge {{
    display: inline-block;
    padding: 1pt 4pt;
    font-size: 6.5pt;
    font-weight: 600;
    border-radius: 3px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
  }}
  .badge-success {{ background: #dcfce7; color: #166534; border: 1px solid #86efac; }}
  .badge-warning {{ background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }}
  .badge-danger {{ background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }}
  .badge-info {{ background: #e0f2fe; color: #075985; border: 1px solid #7dd3fc; }}
  .badge-primary {{ background: #0d5c3a; color: #ffffff; }}

  /* Process Flowchart Representation */
  .workflow-grid {{
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 6pt 0;
    page-break-inside: avoid;
    break-inside: avoid;
  }}
  .workflow-node {{
    flex: 1;
    background: #ffffff;
    border: 1.5pt solid #0d5c3a;
    border-radius: 4px;
    padding: 5pt;
    text-align: center;
    font-size: 7.5pt;
  }}
  .workflow-node .node-title {{
    font-weight: bold;
    color: #0d5c3a;
    margin-bottom: 1.5pt;
  }}
  .workflow-arrow {{
    padding: 0 3pt;
    color: #0d5c3a;
    font-weight: bold;
    font-size: 11pt;
  }}

  /* Running Header */
  .running-header {{
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 7.5pt;
    color: #64748b;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 3pt;
    margin-bottom: 10pt;
  }}

  ul, ol {{
    margin-left: 14pt;
    margin-bottom: 5pt;
  }}
  li {{
    margin-bottom: 1.5pt;
  }}

  .key-value-list dt {{
    font-weight: 700;
    color: #1e293b;
    margin-top: 4pt;
  }}
  .key-value-list dd {{
    margin-left: 8pt;
    margin-bottom: 3pt;
    color: #475569;
  }}
</style>
</head>
<body>

<!-- ==================== COVER PAGE ==================== -->
<div class="cover-container">
  <div class="cover-header">
    <img src="{logo_b64}" alt="Government of Khyber Pakhtunkhwa - Fisheries Department Logo">
    <div class="cover-dept">Government of Khyber Pakhtunkhwa</div>
    <div class="cover-subdept">Directorate General of Fisheries · Civil Secretariat, Peshawar</div>
  </div>

  <div class="cover-title-box">
    <h1>RECREATIONAL FISHERIES LICENSING &amp; MANAGEMENT SYSTEM (RFLMS)</h1>
    <div class="subtitle">Official Administrator User Manual &amp; Standard Operating Procedures (SOPs)</div>
  </div>

  <div class="cover-desc">
    An authoritative operational guide detailing departmental capabilities, territorial security access matrices, 
    e-licence review and approval workflows, walk-in counter processing, anti-poaching enforcement, 
    and policy governance.
  </div>

  <div class="cover-metadata">
    <table>
      <tr><td class="label">Document Ref:</td><td>RFLMS-MAN-ADM-v1.0</td></tr>
      <tr><td class="label">System Version:</td><td>Release 1.0 (Production Pilot)</td></tr>
      <tr><td class="label">Target Audience:</td><td>Super Admins, Provincial Executives (DG), District Fisheries Officers (DFOs), Office Assistants &amp; Field Staff</td></tr>
      <tr><td class="label">Portal URL:</td><td><code>https://[portal-domain]/staff/login</code></td></tr>
      <tr><td class="label">Effective Date:</td><td>September 2026</td></tr>
      <tr><td class="label">Classification:</td><td>Official / Internal Administrative Use</td></tr>
    </table>
  </div>

  <div class="cover-footer">
    Published by Directorate General of Fisheries, Government of Khyber Pakhtunkhwa. All rights reserved.
  </div>
</div>

<!-- ==================== TABLE OF CONTENTS (PAGE 2) ==================== -->
<div class="toc-container">
  <div class="running-header">
    <span>KP Fisheries RFLMS — Admin User Manual</span>
    <span>Table of Contents</span>
  </div>

  <h2 class="toc-title">Table of Contents</h2>

  <div class="toc-item level-1"><span>1. SYSTEM ARCHITECTURE &amp; SECURITY ACCESS MODEL</span><span class="toc-dots"></span><span>Page {toc_pages['sec1']}</span></div>
  <div class="toc-item level-2"><span>1.1 Introduction to the E-Licensing System</span><span class="toc-dots"></span><span>Page {toc_pages['sec1_1']}</span></div>
  <div class="toc-item level-2"><span>1.2 Accessing the Admin Portal (/staff/login)</span><span class="toc-dots"></span><span>Page {toc_pages['sec1_2']}</span></div>
  <div class="toc-item level-2"><span>1.3 Staff Role Hierarchy (Super Admin, Executive, Admin)</span><span class="toc-dots"></span><span>Page {toc_pages['sec1_3']}</span></div>
  <div class="toc-item level-2"><span>1.4 District &amp; Office Scoping Architecture</span><span class="toc-dots"></span><span>Page {toc_pages['sec1_4']}</span></div>
  <div class="toc-item level-2"><span>1.5 Role-Based Access Control (RBAC) &amp; Permission Structure</span><span class="toc-dots"></span><span>Page {toc_pages['sec1_5']}</span></div>

  <div class="toc-item level-1"><span>2. WHAT ADMIN CAN DO (SYSTEM CAPABILITIES MATRIX)</span><span class="toc-dots"></span><span>Page {toc_pages['sec2']}</span></div>
  <div class="toc-item level-2"><span>2.1 Operational &amp; Analytics Dashboard</span><span class="toc-dots"></span><span>Page {toc_pages['sec2_1']}</span></div>
  <div class="toc-item level-2"><span>2.2 Licence Applications Lifecycle Management</span><span class="toc-dots"></span><span>Page {toc_pages['sec2_2']}</span></div>
  <div class="toc-item level-2"><span>2.3 Walk-in Counter Licensing &amp; Instant Issuance</span><span class="toc-dots"></span><span>Page {toc_pages['sec2_3']}</span></div>
  <div class="toc-item level-2"><span>2.4 Water Bodies &amp; Reservoir Master Catalog</span><span class="toc-dots"></span><span>Page {toc_pages['sec2_4']}</span></div>
  <div class="toc-item level-2"><span>2.5 Licence Categories, Bag Limits &amp; Fee Tariffs</span><span class="toc-dots"></span><span>Page {toc_pages['sec2_5']}</span></div>
  <div class="toc-item level-2"><span>2.6 Anti-Poaching &amp; Violation Reports Management</span><span class="toc-dots"></span><span>Page {toc_pages['sec2_6']}</span></div>
  <div class="toc-item level-2"><span>2.7 Fisheries Offices Directory Management</span><span class="toc-dots"></span><span>Page {toc_pages['sec2_7']}</span></div>
  <div class="toc-item level-2"><span>2.8 Staff User Provisioning &amp; Account Control</span><span class="toc-dots"></span><span>Page {toc_pages['sec2_8']}</span></div>
  <div class="toc-item level-2"><span>2.9 Groups &amp; Custom Role Management</span><span class="toc-dots"></span><span>Page {toc_pages['sec2_9']}</span></div>
  <div class="toc-item level-2"><span>2.10 Four-Step Policy Setup &amp; Governance Wizard</span><span class="toc-dots"></span><span>Page {toc_pages['sec2_10']}</span></div>
  <div class="toc-item level-2"><span>2.11 Audit Reporting &amp; Data Exports (CSV / PDF)</span><span class="toc-dots"></span><span>Page {toc_pages['sec2_11']}</span></div>
  <div class="toc-item level-2"><span>2.12 Public QR Code Verification &amp; Privacy Shield</span><span class="toc-dots"></span><span>Page {toc_pages['sec2_12']}</span></div>

  <div class="toc-item level-1"><span>3. HOW ADMIN CAN DO IT (STEP-BY-STEP PROCEDURAL GUIDE)</span><span class="toc-dots"></span><span>Page {toc_pages['sec3']}</span></div>
  <div class="toc-item level-2"><span>3.1 How to Log In &amp; Manage Portal Sessions</span><span class="toc-dots"></span><span>Page {toc_pages['sec3_1']}</span></div>
  <div class="toc-item level-2"><span>3.2 How to Review &amp; Decide on Citizen Applications</span><span class="toc-dots"></span><span>Page {toc_pages['sec3_2']}</span></div>
  <div class="toc-item level-2"><span>3.3 How to Issue Walk-in Counter Licences On-The-Spot</span><span class="toc-dots"></span><span>Page {toc_pages['sec3_3']}</span></div>
  <div class="toc-item level-2"><span>3.4 How to Manage Water Bodies &amp; Geolocation</span><span class="toc-dots"></span><span>Page {toc_pages['sec3_4']}</span></div>
  <div class="toc-item level-2"><span>3.5 How to Configure Licence Categories &amp; Duration Rules</span><span class="toc-dots"></span><span>Page {toc_pages['sec3_5']}</span></div>
  <div class="toc-item level-2"><span>3.6 How to Process &amp; Resolve Violation Incident Reports</span><span class="toc-dots"></span><span>Page {toc_pages['sec3_6']}</span></div>
  <div class="toc-item level-2"><span>3.7 How to Manage Fisheries Offices</span><span class="toc-dots"></span><span>Page {toc_pages['sec3_7']}</span></div>
  <div class="toc-item level-2"><span>3.8 How to Onboard Staff Users &amp; Configure District Scopes</span><span class="toc-dots"></span><span>Page {toc_pages['sec3_8']}</span></div>
  <div class="toc-item level-2"><span>3.9 How to Create Groups &amp; Set Granular Permissions</span><span class="toc-dots"></span><span>Page {toc_pages['sec3_9']}</span></div>
  <div class="toc-item level-2"><span>3.10 How to Execute the 4-Step Policy Setup Wizard</span><span class="toc-dots"></span><span>Page {toc_pages['sec3_10']}</span></div>
  <div class="toc-item level-2"><span>3.11 How to Generate &amp; Print Audit Reports</span><span class="toc-dots"></span><span>Page {toc_pages['sec3_11']}</span></div>
  <div class="toc-item level-2"><span>3.12 How to Verify Licences via Mobile QR Scanning</span><span class="toc-dots"></span><span>Page {toc_pages['sec3_12']}</span></div>

  <div class="toc-item level-1"><span>4. SYSTEM RULES, BUSINESS LOGIC &amp; CONSTRAINTS</span><span class="toc-dots"></span><span>Page {toc_pages['sec4']}</span></div>
  <div class="toc-item level-2"><span>4.1 Closed Breeding Season Enforcement Rules</span><span class="toc-dots"></span><span>Page {toc_pages['sec4_1']}</span></div>
  <div class="toc-item level-2"><span>4.2 The 30 June Seasonal Expiry Rule</span><span class="toc-dots"></span><span>Page {toc_pages['sec4_2']}</span></div>
  <div class="toc-item level-2"><span>4.3 District Boundary &amp; Jurisdiction Scoping Rules</span><span class="toc-dots"></span><span>Page {toc_pages['sec4_3']}</span></div>
  <div class="toc-item level-2"><span>4.4 Payment Reconciliation Rules (Cash vs Bank vs 1Bill)</span><span class="toc-dots"></span><span>Page {toc_pages['sec4_4']}</span></div>

  <div class="toc-item level-1"><span>5. TROUBLESHOOTING &amp; FREQUENTLY ASKED QUESTIONS (FAQ)</span><span class="toc-dots"></span><span>Page {toc_pages['sec5']}</span></div>
</div>

<!-- ==================== SECTION 1 ==================== -->
<div class="running-header">
  <span>KP Fisheries RFLMS — Admin User Manual</span>
  <span>Section 1: Architecture &amp; Security Model</span>
</div>

<h2>1. System Architecture &amp; Security Access Model</h2>

<h3>1.1 Introduction to the E-Licensing System</h3>
<p>
  The <strong>Recreational Fisheries Licensing &amp; Management System (RFLMS)</strong> is an enterprise e-governance platform 
  developed for the Directorate General of Fisheries, Government of Khyber Pakhtunkhwa. The platform automates the end-to-end 
  licensing lifecycle for recreational angling, cold-water trout waters, commercial riverine reaches, and reservoir fisheries.
</p>
<p>
  Key strategic goals achieved through the Admin Portal include:
</p>
<ul>
  <li><strong>Elimination of Paper Challans:</strong> Digital verification of fees via 1Bill / PSID, direct bank deposit slips, or counter cash collections.</li>
  <li><strong>Conservation &amp; Anti-Poaching:</strong> Immediate enforcement of statutory closed breeding seasons and bag limits across 36 districts of KP.</li>
  <li><strong>Counter Servicing for Walk-in Anglers:</strong> Enabling field staff and district offices to register walk-in anglers and issue valid QR-coded e-licences on-the-spot in under 60 seconds.</li>
  <li><strong>Decentralized District Administration:</strong> Strict data isolation guaranteeing that District Fisheries Officers (DFOs) only view and act upon records within their territorial jurisdiction.</li>
</ul>

<h3>1.2 Accessing the Admin Portal</h3>
<p>
  The administration portal is segregated from citizen-facing routes to ensure heightened security. 
  Staff members must authenticate using their authorized departmental credentials:
</p>
<div class="callout callout-info">
  <strong>Official Staff Portal Endpoint:</strong>
  URL: <code>https://[portal-domain]/staff/login</code><br>
  Accepts: Official registered email address and alphanumeric password. Citizens attempting to authenticate on this endpoint are automatically rejected with access denied notices.
</div>

<h3>1.3 Staff Role Hierarchy</h3>
<p>
  RFLMS enforces a strict user categorization structure with three core staff classifications:
</p>
<table class="data-table">
  <thead>
    <tr>
      <th style="width: 22%;">Account Type</th>
      <th style="width: 33%;">Target Designation</th>
      <th style="width: 45%;">System Authority &amp; Access Scope</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><strong>Super Admin</strong><br><span class="badge badge-danger">Unrestricted</span></td>
      <td>Provincial IT Administrator / System Overseer</td>
      <td>Full unrestricted access across all 36 districts, all 15 functional modules, and all system setup configurations. Permission matrix evaluation is bypassed. Can provision other staff users, modify templates, and configure QR privacy.</td>
    </tr>
    <tr>
      <td><strong>Executive</strong><br><span class="badge badge-warning">Oversight</span></td>
      <td>Director General (DG), Directors, Secretariat Observers</td>
      <td>Province-wide analytical view across all districts and water bodies. High-level dashboard visibility, financial revenue reconciliation, and macro audit reporting.</td>
    </tr>
    <tr>
      <td><strong>Admin (Staff)</strong><br><span class="badge badge-success">Operational</span></td>
      <td>District Fisheries Officers (DFOs), Assistant Directors, Office Assistants, Field Enforcement Inspectors</td>
      <td>Operational staff bound to specific territorial districts and offices. Actions (View, Create, Edit, Delete, Status, Approve) are governed strictly by assigned Permission Groups.</td>
    </tr>
  </tbody>
</table>

<h3>1.4 District &amp; Office Scoping Architecture</h3>
<p>
  Data sovereignty and administrative compartmentalization are fundamental to the RFLMS security design. 
  Except for Super Admins and users with the <code>all_districts</code> flag explicitly enabled, every operational staff user 
  is bound to a designated array of <strong>Allowed District IDs</strong> and <strong>Assigned Office IDs</strong>.
</p>

<div class="workflow-grid">
  <div class="workflow-node">
    <div class="node-title">Admin Logs In</div>
    <div>System loads profile &amp; scopes</div>
  </div>
  <div class="workflow-arrow">&rarr;</div>
  <div class="workflow-node">
    <div class="node-title">Scope Resolution</div>
    <div>Evaluates <code>all_districts</code> flag or district array</div>
  </div>
  <div class="workflow-arrow">&rarr;</div>
  <div class="workflow-node">
    <div class="node-title">Query Isolation</div>
    <div>Automatic <code>forUserDistricts()</code> database filter</div>
  </div>
  <div class="workflow-arrow">&rarr;</div>
  <div class="workflow-node">
    <div class="node-title">Action Enforcement</div>
    <div>Controller rejects cross-district manipulation with HTTP 403</div>
  </div>
</div>

<div class="callout callout-warning">
  <strong>Strict Territorial Enforcement Rule:</strong>
  If an officer assigned exclusively to <em>District Swat</em> attempts to open an application or water body from <em>District Mansehra</em> (via direct URL manipulation or API call), the system aborts execution immediately with <code>403: Application outside your district scope</code>.
</div>

<h3>1.5 Role-Based Access Control (RBAC) &amp; Permission Structure</h3>
<p>
  RFLMS utilizes a multi-layered Role-Based Access Control (RBAC) engine. Permissions are assigned to <strong>Groups</strong>, 
  and users can be assigned to one or more active groups. The system dynamically evaluates permissions across 15 distinct 
  functional modules against 6 granular operational actions:
</p>

<table class="data-table">
  <thead>
    <tr>
      <th style="width: 15%;">Action Key</th>
      <th style="width: 25%;">Action Name</th>
      <th style="width: 60%;">Operational Capability Granted</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><code>view</code></td>
      <td>View / Read</td>
      <td>Access indexes, view dashboards, inspect detail dossiers, read reports.</td>
    </tr>
    <tr>
      <td><code>create</code></td>
      <td>Create / Register</td>
      <td>Submit walk-in counter applications, add new reservoirs, register new staff or groups.</td>
    </tr>
    <tr>
      <td><code>edit</code></td>
      <td>Edit / Modify</td>
      <td>Update reservoir metadata, alter office records, modify category tariffs.</td>
    </tr>
    <tr>
      <td><code>delete</code></td>
      <td>Delete / Archive</td>
      <td>Remove non-critical master records or custom groups (subject to dependency checks).</td>
    </tr>
    <tr>
      <td><code>status</code></td>
      <td>Status Update</td>
      <td>Transition illegal fishing violation reports from <em>Pending</em> to <em>Under Investigation</em> or <em>Resolved</em>.</td>
    </tr>
    <tr>
      <td><code>approve</code></td>
      <td>Decision / Issue</td>
      <td>Final approval of licence applications, digital signature endorsement, instant licence card generation, formal rejections, and returning files for info.</td>
    </tr>
  </tbody>
</table>

<div class="page-break-before"></div>

<!-- ==================== SECTION 2 ==================== -->
<div class="running-header">
  <span>KP Fisheries RFLMS — Admin User Manual</span>
  <span>Section 2: What Admin Can Do</span>
</div>

<h2>2. What Admin Can Do (System Capabilities Matrix)</h2>
<p>
  This section provides an exhaustive summary of every capability, tool, and feature available to departmental administrators. 
  Access to specific capabilities depends on the administrator's assigned group permissions.
</p>

<h3>2.1 Operational &amp; Analytics Dashboard</h3>
<p>
  The RFLMS Dashboard serves as the central operational cockpit for administrators upon login. 
  It calculates real-time metrics scoped to the administrator's assigned jurisdiction:
</p>
<ul>
  <li><strong>Pending Applications KPI:</strong> Real-time count of citizen and walk-in applications requiring administrative review (aggregating <em>Submitted</em>, <em>Under Review</em>, and <em>Information Required</em>).</li>
  <li><strong>Approved Today KPI:</strong> Number of licences sanctioned and issued during the current calendar day.</li>
  <li><strong>Issued Licences KPI:</strong> Total count of currently valid, active e-licences operating within the administrator's districts.</li>
  <li><strong>Open Violations KPI:</strong> Number of reported illegal fishing incidents currently pending field triage or active investigation.</li>
  <li><strong>Active Water Bodies KPI:</strong> Total count of gazetted reservoirs, dams, rivers, and streams active in the catalog.</li>
  <li><strong>Verified Revenue KPI:</strong> Cumulative financial total (in PKR) of verified application fees successfully booked through counter cash, bank deposits, or 1Bill.</li>
  <li><strong>Recent Action Queues:</strong> Immediate table feeds displaying the 8 most recent licence applications and 5 most recent violation incident alerts for rapid action.</li>
</ul>

<h3>2.2 Licence Applications Lifecycle Management</h3>
<p>
  Administrators can oversee, inspect, and adjudicate all recreational angling applications submitted via the public portal, 
  mobile apps, or physical district counters:
</p>
<table class="data-table">
  <thead>
    <tr>
      <th style="width: 25%;">Capability</th>
      <th style="width: 75%;">Detailed Functionality</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><strong>Application Queues &amp; Filtering</strong></td>
      <td>Filter applications by status (<em>Submitted</em>, <em>Under Review</em>, <em>Info Required</em>, <em>Approved</em>, <em>Rejected</em>, <em>Cancelled</em>). Perform instant full-text search across Application Tracking Numbers, Citizen Names, and Email Addresses.</td>
    </tr>
    <tr>
      <td><strong>Dossier Verification</strong></td>
      <td>Inspect applicant's full demographic profile, verified CNIC, residential address, emergency contact, selected water body, requested licence category, and fishing start/end dates.</td>
    </tr>
    <tr>
      <td><strong>Financial Proof Audit</strong></td>
      <td>View uploaded bank deposit slips, digital payment receipts, or 1Bill / PSID transaction codes. Confirm whether fee amount matches current gazetted category tariff.</td>
    </tr>
    <tr>
      <td><strong>Sanction &amp; Issuance</strong></td>
      <td>Sanction the application with an optional officer remark. Instantly triggers the digital issuance engine: generates unique Licence Number (e.g. <code>RFL-2026-00123</code>), calculates exact expiry date, generates cryptographically secured QR token, and creates active Licence Card.</td>
    </tr>
    <tr>
      <td><strong>Return for Correction</strong></td>
      <td>Flag applications with deficient documentation (e.g. illegible payment slip) using the <em>Request More Info</em> action. The applicant receives notification with specific officer remarks to correct and re-upload.</td>
    </tr>
    <tr>
      <td><strong>Formal Rejection</strong></td>
      <td>Decline ineligible applications (e.g. fraudulent slips, blacklist) with mandatory official justification notes recorded for legal transparency.</td>
    </tr>
  </tbody>
</table>

<h3>2.3 Walk-in Counter Licensing &amp; Instant Issuance</h3>
<p>
  To service anglers who lack smartphones or internet access, administrators can process walk-in applications at district 
  counters (via <code>/admin/applications/create</code>). This capability features a streamlined dual-mode workflow:
</p>

<div class="workflow-grid">
  <div class="workflow-node">
    <div class="node-title">Step 1: Citizen Mode</div>
    <div>Existing Citizen OR Register New Walk-in</div>
  </div>
  <div class="workflow-arrow">&rarr;</div>
  <div class="workflow-node">
    <div class="node-title">Step 2: Permit Selection</div>
    <div>Choose Reservoir, Category &amp; Date</div>
  </div>
  <div class="workflow-arrow">&rarr;</div>
  <div class="workflow-node">
    <div class="node-title">Step 3: Payment Collection</div>
    <div>Counter Cash, Slip, or 1Bill PSID</div>
  </div>
  <div class="workflow-arrow">&rarr;</div>
  <div class="workflow-node">
    <div class="node-title">Step 4: Instant Issue</div>
    <div>Auto-Approve issues licence card on the spot</div>
  </div>
</div>

<p>Specific counter capabilities include:</p>
<ul>
  <li><strong>Instant Citizen Registration:</strong> Capture full CNIC (formatted <code>12345-1234567-1</code>), Full Name, Father's Name, Mobile Number, Date of Birth, Gender, District of Residence, Postal Address, Emergency Contact, and photograph.</li>
  <li><strong>Automated Credential Generation:</strong> If applicant lacks an email, the system automatically provisions an official alias (<code>walkin_[cnic]@fisheries.kp.gov.pk</code>) and a cryptographically secure random password.</li>
  <li><strong>Counter Cash Reconciliation:</strong> Allows immediate recording of counter cash payment with auto-generated receipt code (<code>COUNTER-CASH-[timestamp]</code>) and automated payment ledger creation.</li>
  <li><strong>On-the-Spot E-Licence Printing:</strong> Checking the <em>"Auto-Approve &amp; Issue E-Licence Card On-the-spot"</em> box bypasses review queues, immediately activating the licence and rendering the printable permit card with QR code.</li>
</ul>

<div class="callout callout-success">
  <strong>Efficiency Metric:</strong>
  Using the Walk-in Counter module, an administrative assistant can register an uneducated angler, collect cash fee, issue an official government fisheries licence, and hand over a printed QR card in less than 2 minutes.
</div>

<h3>2.4 Water Bodies &amp; Reservoir Master Catalog</h3>
<p>
  Administrators manage the provincial inventory of fishing waters (via <code>/admin/reservoirs</code>). Capabilities include:
</p>
<ul>
  <li><strong>Geographic &amp; Ecological Classification:</strong> Catalog waters under 6 structural categories: <em>Dam / Reservoir</em>, <em>River</em>, <em>Stream</em>, <em>Canal</em>, <em>Headworks</em>, or <em>Other</em>.</li>
  <li><strong>Trout Water Stratification:</strong> Classify fisheries zones into <em>Trout</em> (cold-water, e.g. Swat, Kumrat, Kaghan), <em>Non-trout</em> (warm-water cyprinids), <em>Mixed</em>, or <em>Unknown</em>.</li>
  <li><strong>GPS Geolocation:</strong> Record accurate decimal latitude (bounded 30.00° to 37.00° N) and longitude (bounded 69.00° to 75.00° E) for satellite mapping.</li>
  <li><strong>E-Licensing Eligibility Toggle:</strong> Administrators can individually or collectively open or close any water body for public electronic applications (<code>is_open_for_licensing: true/false</code>).</li>
  <li><strong>Management &amp; Lease Notes:</strong> Document species diversity (e.g. Brown Trout, Rainbow Trout, Mahseer), angling restrictions, commercial netting leases, and local safety precautions.</li>
</ul>

<h3>2.5 Licence Categories, Bag Limits &amp; Fee Tariffs</h3>
<p>
  Through the <code>/admin/categories</code> module, administrators maintain the tariff schedule, legal catch quotas, and duration parameters:
</p>
<table class="data-table">
  <thead>
    <tr>
      <th style="width: 20%;">Category Parameter</th>
      <th style="width: 80%;">Operational Control &amp; Configuration Details</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><strong>Unique Category Code</strong></td>
      <td>Alphanumeric identifier (e.g. <code>TROUT-DAILY</code>, <code>ANGLING-SEASONAL</code>, <code>MAHSEER-SPECIAL</code>).</td>
    </tr>
    <tr>
      <td><strong>Duration Type</strong></td>
      <td>Configure duration rules: <em>Daily</em> (1 day), <em>Weekly</em> (7 days), <em>Monthly</em> (30 days), <em>Seasonal</em> (governed by 30 June rule), or <em>Custom</em>.</td>
    </tr>
    <tr>
      <td><strong>Expiry Calculation Rule</strong></td>
      <td>
        <strong>From Start Date:</strong> Adds duration days directly to the selected fishing start date.<br>
        <strong>Fixed Date / Season End:</strong> Licence validity strictly terminates on the prescribed seasonal date (e.g. June 30), regardless of when applied.
      </td>
    </tr>
    <tr>
      <td><strong>Fee Tariffs (PKR)</strong></td>
      <td>Configure statutory fee amount in PKR per category. Updated instantly across all citizen portals.</td>
    </tr>
    <tr>
      <td><strong>Max Fish Limit (Bag Limit)</strong></td>
      <td>Specifies legal daily catch limit per angler (e.g. 6 fish per day for Trout waters) to prevent over-fishing.</td>
    </tr>
    <tr>
      <td><strong>Instructions &amp; Terms</strong></td>
      <td>Custom regulatory terms printed on the physical licence card (e.g. single-hook artificial lure only; no live bait).</td>
    </tr>
  </tbody>
</table>

<h3>2.6 Anti-Poaching &amp; Violation Reports Management</h3>
<p>
  RFLMS includes a dedicated surveillance and anti-poaching module (<code>/admin/violations</code>) that collects incident reports 
  from both field enforcement staff and whistleblowing citizens:
</p>
<ul>
  <li><strong>Incident Dossier Inspection:</strong> Review report tracking number, date/time of occurrence, reporting party (or anonymous citizen submission), and narrative description of illegal activity.</li>
  <li><strong>Photographic Evidence Review:</strong> Examine photographic proof submitted by field teams or citizens (e.g. seized nets, poison canisters, poached catch).</li>
  <li><strong>Satellite Geolocation:</strong> One-click interactive Google Maps integration plotting the exact latitude and longitude where the violation took place.</li>
  <li><strong>Three-Stage Legal Case Lifecycle:</strong>
    <ul>
      <li><span class="badge badge-danger">Pending</span>: Newly filed report awaiting departmental review and officer assignment.</li>
      <li><span class="badge badge-warning">Under Investigation</span>: Assigned to local fisheries inspector for ground verification and raid.</li>
      <li><span class="badge badge-success">Resolved</span>: Legal action completed (e.g. fine levied, FIR lodged, gear confiscated) with mandatory resolution notes.</li>
    </ul>
  </li>
  <li><strong>Investigating Officer Attribution:</strong> Automatic audit recording of the administrator who investigated and resolved the incident.</li>
</ul>

<h3>2.7 Fisheries Offices Directory Management</h3>
<p>
  Administrators manage the administrative network of regional directorates, district fisheries offices, and hatchery outposts (<code>/admin/offices</code>):
</p>
<ul>
  <li><strong>Office Directory Creation:</strong> Register official facility names (e.g. <em>District Fisheries Office Mardan</em>, <em>Swat Trout Hatchery</em>).</li>
  <li><strong>District Binding:</strong> Associate facilities to their respective geographic district for automated routing of walk-in applications.</li>
  <li><strong>Contact Communication Channels:</strong> Maintain official office telephone numbers, contact email addresses, and postal locations.</li>
</ul>

<h3>2.8 Staff User Provisioning &amp; Account Control</h3>
<p>
  Departmental user management (<code>/admin/users</code>) allows Super Admins and authorized managers to govern staff access:
</p>
<ul>
  <li><strong>Account Provisioning:</strong> Create staff accounts with Full Name, Email, Mobile, CNIC, Father's Name, Date of Birth, Gender, Address, Emergency Contact, and Designation Label.</li>
  <li><strong>District Scoping Controls:</strong>
    <ul>
      <li><em>Global Scope (All Districts):</em> Grants provincial jurisdiction across all 36 districts.</li>
      <li><em>Multi-District Selection:</em> Selectively check one or more specific districts (e.g. Abbottabad + Haripur). The user cannot view or edit data outside these selected boundaries.</li>
    </ul>
  </li>
  <li><strong>Office Level Binding:</strong> Select specific fisheries offices where the staff member is stationed, organized neatly by district with bulk selection shortcuts.</li>
  <li><strong>Role &amp; Group Assignment:</strong> Assign staff members to one or multiple Permission Groups. Module rights are unioned across all assigned groups.</li>
  <li><strong>Account Deactivation:</strong> Instantly revoke access via the <em>Active</em> switch without deleting audit trails or past decision history.</li>
</ul>

<h3>2.9 Groups &amp; Custom Role Management (RBAC)</h3>
<p>
  Through <code>/admin/groups</code>, administrators can construct customized operational profiles rather than hardcoding rights:
</p>
<ul>
  <li><strong>Group Creation:</strong> Name and describe customized roles (e.g. <em>Trout Belt Enforcement Wing</em>, <em>Headquarters Revenue Auditor</em>).</li>
  <li><strong>Granular Module Matrix:</strong> Independently toggle <code>view</code>, <code>create</code>, <code>edit</code>, <code>delete</code>, <code>status</code>, and <code>approve</code> permissions across all 15 system modules.</li>
  <li><strong>Permission Blueprint Presets:</strong> Quick-fill buttons that automatically apply standard role templates:
    <ul>
      <li><em>DG / Provincial:</em> Full provincial oversight, report exports, setting configurations, and approval powers.</li>
      <li><em>District Officer (DFO):</em> District-scoped application adjudication, violation resolution, reservoir management.</li>
      <li><em>Office Assistant:</em> Queue management, walk-in counter registrations, fee collection.</li>
      <li><em>Field Officer:</em> Field licence validation, QR scanning, violation reporting and status updates.</li>
    </ul>
  </li>
</ul>

<h3>2.10 Four-Step Policy Setup &amp; Governance Wizard</h3>
<p>
  Located at <code>/admin/setup</code>, this master governance console allows administrators to calibrate province-wide 
  licensing policy before or during the angling season:
</p>
<table class="data-table">
  <thead>
    <tr>
      <th style="width: 18%;">Setup Step</th>
      <th style="width: 25%;">Governance Area</th>
      <th style="width: 57%;">Administrative Control &amp; System Impact</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><strong>Step 1</strong><br><span class="badge badge-primary">Fees &amp; Seasons</span></td>
      <td>Fee Tariffs, 30 June Rule &amp; Closed Breeding Season</td>
      <td>
        • Update base fee amounts for all categories in a unified table.<br>
        • Configure annual Seasonal Expiry Month-Day (default <code>06-30</code>).<br>
        • Define global statutory Closed Breeding Season (Start Month/Day to End Month/Day, e.g. June 1 to July 31). Blocks citizens from picking dates during spawning.
      </td>
    </tr>
    <tr>
      <td><strong>Step 2</strong><br><span class="badge badge-primary">Water Bodies</span></td>
      <td>E-Licence Water Body Eligibility</td>
      <td>
        • Mass review of all provincial reservoirs and rivers.<br>
        • Bulk toggle water bodies as <em>Eligible for E-Licence (Open)</em>, <em>Closed</em>, or <em>Undecided</em>.<br>
        • Enforce mandatory explicit e-licence verification policy flag.
      </td>
    </tr>
    <tr>
      <td><strong>Step 3</strong><br><span class="badge badge-primary">Staff Blueprints</span></td>
      <td>Permission Templates Blueprint</td>
      <td>
        • Review baseline permission matrices across DG, DFO, Assistant, and Field roles.<br>
        • Audit active staff distribution across templates before saving policy confirmations.
      </td>
    </tr>
    <tr>
      <td><strong>Step 4</strong><br><span class="badge badge-primary">QR Privacy</span></td>
      <td>Public QR Privacy Shield</td>
      <td>
        • Toggle public QR holder display between <em>Masked</em> (e.g. <code>M*******d A*i K**n</code>) and <em>Full</em>.<br>
        • Enable or disable public CNIC visibility on unauthenticated scans. Protects citizen personal data while maintaining field verifiability.
      </td>
    </tr>
  </tbody>
</table>

<h3>2.11 Audit Reporting &amp; Data Exports</h3>
<p>
  The reporting engine (<code>/admin/reports</code>) allows administrators to extract high-fidelity departmental records:
</p>
<ul>
  <li><strong>Dataset Selection:</strong> Extract <em>Licence Applications</em>, <em>Issued Licences</em>, or <em>Violation Reports</em>.</li>
  <li><strong>Temporal Filtering:</strong> Filter data by customizable <em>Date From</em> and <em>Date To</em> ranges.</li>
  <li><strong>Status Granularity:</strong> Filter by specific lifecycle stages (e.g. only <code>approved</code> licences, or only <code>resolved</code> violations).</li>
  <li><strong>Excel / LibreOffice CSV Export:</strong> Generates instant spreadsheet downloads formatted with UTF-8 BOM encoding to guarantee perfect rendering in Microsoft Excel without character corruption.</li>
  <li><strong>Official Printable PDF:</strong> Generates clean, printer-friendly summary reports with official departmental header, filter metadata, and tabular layout for official briefings.</li>
</ul>

<h3>2.12 Public QR Code Verification &amp; Privacy Shield</h3>
<p>
  Every issued e-licence is embedded with a cryptographically unique QR token. 
  Administrators can understand and configure how this verification mechanism behaves:
</p>

<div class="workflow-grid">
  <div class="workflow-node">
    <div class="node-title">Angler Presents Permit</div>
    <div>Phone screen or paper card</div>
  </div>
  <div class="workflow-arrow">&rarr;</div>
  <div class="workflow-node">
    <div class="node-title">QR Scanner Action</div>
    <div>Scanned via smartphone camera</div>
  </div>
  <div class="workflow-arrow">&rarr;</div>
  <div class="workflow-node">
    <div class="node-title">Context Evaluation</div>
    <div>Anonymous Citizen vs Logged-in Staff</div>
  </div>
  <div class="workflow-arrow">&rarr;</div>
  <div class="workflow-node">
    <div class="node-title">Adaptive Rendering</div>
    <div>Public: Masked<br>Staff: Full Demographic Profile</div>
  </div>
</div>

<p>The system provides dual-mode verification security:</p>
<ul>
  <li><strong>Public / Anonymous Scans:</strong> If scanned by a member of the public or third party, the holder's personal identity is protected via dynamic masking algorithms (e.g. <code>M*******d A*i K**n</code>; CNIC hidden or masked as <code>********1234</code>).</li>
  <li><strong>Departmental Staff Verification:</strong> When authenticated fisheries officers scan the QR code via their mobile devices or administrative portal, the system decrypts and displays full verified credentials, photo identification, validity status, and water body authorization.</li>
</ul>

<div class="page-break-before"></div>

<!-- ==================== SECTION 3 ==================== -->
<div class="running-header">
  <span>KP Fisheries RFLMS — Admin User Manual</span>
  <span>Section 3: How Admin Can Do It</span>
</div>

<h2>3. How Admin Can Do It (Step-by-Step Operating Procedures)</h2>
<p>
  This section serves as a practical, procedural field manual. Each subsection provides precise, click-by-click instructions 
  for executing administrative tasks within the portal.
</p>

<h3>3.1 How to Log In &amp; Manage Portal Sessions</h3>
<div class="step-box">
  <div class="step-number">PROCEDURE 3.1</div>
  <div class="step-title">Staff Portal Authentication</div>
  <ol>
    <li>Launch any modern web browser (Google Chrome, Mozilla Firefox, Microsoft Edge, or Safari).</li>
    <li>Navigate to the official staff login URL: <code>https://[portal-domain]/staff/login</code>.</li>
    <li>In the <strong>Email</strong> field, enter your official registered departmental email address (e.g. <code>dfo.peshawar@fisheries.kp.gov.pk</code>).</li>
    <li>In the <strong>Password</strong> field, input your secure account password.</li>
    <li>(Optional) Check the <strong>Remember me</strong> box if you are operating on a private, secured workstation.</li>
    <li>Click the green <strong>Sign in</strong> button.</li>
    <li>Upon successful authentication, the system automatically redirects you to the <strong>Admin Dashboard</strong> (<code>/admin</code>).</li>
  </ol>
</div>
<div class="callout callout-warning">
  <strong>Session Security:</strong> Always click the yellow <strong>Logout</strong> button in the top navigation bar when leaving your workstation unattended. Staff sessions automatically expire after 120 minutes of inactivity.
</div>

<h3>3.2 How to Review &amp; Decide on Citizen Applications</h3>
<p>
  When citizens submit licence applications online, they appear in the department's review queue. 
  Follow these steps to adjudicate an application:
</p>

<div class="step-box">
  <div class="step-number">PROCEDURE 3.2-A</div>
  <div class="step-title">Locating &amp; Inspecting an Application Dossier</div>
  <ol>
    <li>In the left sidebar navigation, click on <strong>Applications</strong> (<code>/admin/applications</code>).</li>
    <li>Use the top filter bar to narrow down records:
      <ul>
        <li>Select <strong>Status:</strong> <code>under_review</code> or <code>submitted</code> to view pending files.</li>
        <li>In the <strong>Search</strong> box, type the Application Tracking No (e.g. <code>APP-2026-00042</code>) or the applicant's name/email.</li>
      </ul>
    </li>
    <li>In the results table, locate the target record and click the blue <strong>View</strong> button.</li>
    <li>The system opens the detailed dossier page (<code>/admin/applications/{{id}}</code>).</li>
    <li>Review the left-hand column containing:
      <ul>
        <li><strong>Citizen Details:</strong> Full Name, Email, CNIC, and phone number.</li>
        <li><strong>Water Body &amp; District:</strong> Ensure the requested water body is located in your jurisdiction.</li>
        <li><strong>Category &amp; Dates:</strong> Verify fishing start date and duration.</li>
        <li><strong>Fee &amp; Payment Method:</strong> Verify amount (PKR), payment method, and PSID if applicable.</li>
      </ul>
    </li>
    <li>Click the <strong>View payment receipt</strong> link to inspect the uploaded bank slip or deposit voucher in a new tab.</li>
  </ol>
</div>

<div class="step-box">
  <div class="step-number">PROCEDURE 3.2-B</div>
  <div class="step-title">Executing a Decision (Approve / Request Info / Reject)</div>
  <p>In the right-hand column titled <strong>Decision</strong>, choose one of the three available actions:</p>
  
  <h4 style="color: #166534; margin-top: 4pt;">Option 1: Approving the Application &amp; Issuing Licence</h4>
  <ol>
    <li>In the first form box, optionally type any audit notes into the <strong>Remarks (optional)</strong> textarea.</li>
    <li>Click the solid green button: <strong>Approve &amp; issue licence</strong>.</li>
    <li><em>System Execution:</em> Status changes to <code>approved</code>, a permanent Licence Number is generated (e.g. <code>RFL-2026-00089</code>), the QR verification token is minted, and the applicant receives an automated approval notification.</li>
    <li>A green confirmation alert appears: <em>"Approved. Licence RFL-2026-XXXXX issued."</em></li>
  </ol>

  <h4 style="color: #92400e; margin-top: 5pt;">Option 2: Returning Application for More Information</h4>
  <ol>
    <li>If the payment slip is blurry or profile details are incomplete, navigate to the middle form box.</li>
    <li>In the <strong>Request info (required)</strong> textarea, explain clearly what the applicant must correct (e.g. <em>"Please upload a clear picture of the National Bank deposit slip showing the transaction scroll number."</em>).</li>
    <li>Click the outline button: <strong>Request more info</strong>.</li>
    <li><em>System Execution:</em> Status transitions to <code>info_required</code>. The citizen is notified and can resubmit documents via their portal.</li>
  </ol>

  <h4 style="color: #991b1b; margin-top: 5pt;">Option 3: Formally Rejecting an Ineligible Application</h4>
  <ol>
    <li>If the application is fraudulent or violates provincial regulations, navigate to the bottom form box.</li>
    <li>In the <strong>Reject reason (required)</strong> textarea, input the legal or administrative justification (e.g. <em>"Invalid bank challan. Record does not match Treasury deposit records."</em>).</li>
    <li>Click the red outline button: <strong>Reject</strong>.</li>
    <li><em>System Execution:</em> Status transitions to <code>rejected</code>. Further processing is permanently terminated.</li>
  </ol>
</div>

<h3>3.3 How to Issue Walk-in Counter Licences On-The-Spot</h3>
<p>
  This is the most frequent daily operation for counter staff and office assistants. 
  Follow these comprehensive steps to service a walk-in angler at the fisheries counter:
</p>

<div class="step-box">
  <div class="step-number">PROCEDURE 3.3</div>
  <div class="step-title">End-to-End Walk-in Licence Issuance</div>
  <ol>
    <li>From the left sidebar, click <strong>Applications</strong>, then click the green <strong>Walk-in Application</strong> button in the top right corner (or open <code>/admin/applications/create</code>).</li>
    
    <li style="margin-top: 4pt;"><strong>Step 1: Choose Registration Mode</strong>
      <ul>
        <li>If the angler has previously registered with the department, select radio button <strong>"Select Existing Citizen"</strong>. In the dropdown that appears, select the citizen's profile (searchable by Name, CNIC, and Email).</li>
        <li>If the angler is visiting for the first time, keep the default radio button: <strong>"Register New Walk-in Citizen"</strong>.</li>
      </ul>
    </li>

    <li style="margin-top: 4pt;"><strong>Step 2: Enter New Citizen Profile (if registering new)</strong>
      <table class="data-table" style="margin: 4pt 0;">
        <thead>
          <tr>
            <th style="width: 25%;">Field Name</th>
            <th style="width: 20%;">Requirement</th>
            <th style="width: 55%;">Input Guidelines</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Full Name</td>
            <td><span class="badge badge-danger">Required</span></td>
            <td>Enter applicant's complete name exactly as printed on their CNIC.</td>
          </tr>
          <tr>
            <td>CNIC No.</td>
            <td><span class="badge badge-danger">Required</span></td>
            <td>Must adhere to standard 13-digit Pakistani format: <code>12345-1234567-1</code>.</td>
          </tr>
          <tr>
            <td>Father / Guardian Name</td>
            <td><span class="badge badge-danger">Required</span></td>
            <td>Enter father or husband's name as per CNIC.</td>
          </tr>
          <tr>
            <td>Mobile Number</td>
            <td><span class="badge badge-danger">Required</span></td>
            <td>Active mobile number for SMS notifications: <code>03xx-xxxxxxx</code>.</td>
          </tr>
          <tr>
            <td>Email Address</td>
            <td><span class="badge badge-info">Optional</span></td>
            <td>If applicant has an email, enter it. If left blank, system auto-generates: <code>walkin_[cnic]@fisheries.kp.gov.pk</code>.</td>
          </tr>
          <tr>
            <td>Date of Birth</td>
            <td><span class="badge badge-danger">Required</span></td>
            <td>Select DOB from calendar picker. Must be in the past.</td>
          </tr>
          <tr>
            <td>Gender</td>
            <td><span class="badge badge-danger">Required</span></td>
            <td>Select <em>Male</em>, <em>Female</em>, or <em>Other</em>.</td>
          </tr>
          <tr>
            <td>Residence District</td>
            <td><span class="badge badge-danger">Required</span></td>
            <td>Select the citizen's home domicile district from dropdown.</td>
          </tr>
          <tr>
            <td>Postal Address</td>
            <td><span class="badge badge-danger">Required</span></td>
            <td>Complete residential address.</td>
          </tr>
          <tr>
            <td>Emergency Contact</td>
            <td><span class="badge badge-danger">Required</span></td>
            <td>Phone number or name/relationship of emergency contact.</td>
          </tr>
          <tr>
            <td>Angler Profile Photo</td>
            <td><span class="badge badge-info">Optional</span></td>
            <td>Upload portrait image (JPEG/PNG, max 2MB). Printed on physical licence card.</td>
          </tr>
        </tbody>
      </table>
    </li>

    <li style="margin-top: 4pt;"><strong>Step 3: Select Licence Details (Right Column)</strong>
      <ul>
        <li><strong>Water Body / Reservoir:</strong> Select destination water body (e.g. <em>Khanpur Dam</em> or <em>Swat River Trout Reach</em>). Only waters within your administrative district scope are selectable.</li>
        <li><strong>Licence Category:</strong> Select licence type (e.g. <em>Daily Angling Permit — 500 PKR</em> or <em>Seasonal Angling — 3,000 PKR</em>). Fee and validity days are displayed automatically.</li>
        <li><strong>Fishing Start Date:</strong> Pick the starting date. Defaults to today. <em>Note: If selected date falls within the statutory closed breeding season, the form will reject submission.</em></li>
      </ul>
    </li>

    <li style="margin-top: 4pt;"><strong>Step 4: Record Payment &amp; Sanction</strong>
      <ul>
        <li><strong>Payment Collection Method:</strong> Select <code>Counter Cash Payment (Instant Issue)</code> for physical cash handovers. Other options: <code>Online Bank Transfer</code>, <code>Bank Deposit Slip</code>, or <code>1Bill / PSID System</code>.</li>
        <li><strong>Payment Reference / Receipt No.:</strong> If cash, you may enter a receipt number or leave blank (system assigns auto reference). If bank slip, enter bank scroll reference.</li>
        <li><strong>Attach Payment Slip:</strong> (Optional for cash; recommended for bank deposits).</li>
        <li><strong>Officer Processing Remarks:</strong> Enter brief audit note (defaults to: <em>"Walk-in application registered and counter payment received."</em>).</li>
        <li><strong>Auto-Approve &amp; Issue E-Licence Checkbox:</strong> Ensure this checkbox is <strong>CHECKED</strong> (<span class="badge badge-success">Enabled</span>). This triggers immediate licence generation without manual queue review.</li>
      </ul>
    </li>

    <li style="margin-top: 4pt;"><strong>Step 5: Submit &amp; Print Card</strong>
      <ul>
        <li>Click the large green button: <strong>Submit &amp; Process Walk-in Application</strong>.</li>
        <li>The system creates the user, generates application, records payment ledger, activates licence, and redirects to the completed Application view with success alert: <em>"Walk-in licence issued immediately. Licence No: RFL-2026-XXXXX"</em>.</li>
        <li>Click the <strong>Print Licence Card</strong> button to output the official card on your counter card printer or standard A4 paper.</li>
      </ul>
    </li>
  </ol>
</div>

<h3>3.4 How to Manage Water Bodies &amp; Geolocation</h3>
<p>
  To maintain accurate geographic and regulatory data across all provincial waters (<code>/admin/reservoirs</code>):
</p>

<div class="step-box">
  <div class="step-number">PROCEDURE 3.4-A</div>
  <div class="step-title">Adding a New Reservoir or River Reach</div>
  <ol>
    <li>Navigate to <strong>Water bodies</strong> in the sidebar (<code>/admin/reservoirs</code>).</li>
    <li>Click the green button: <strong>Add water body</strong> (<code>/admin/reservoirs/create</code>).</li>
    <li>Complete the configuration fields:
      <ul>
        <li><strong>District:</strong> Select the geographic district where the water body is located.</li>
        <li><strong>Supervising Office:</strong> Select the local fisheries office responsible for overseeing this water body.</li>
        <li><strong>Water Body Name:</strong> Official gazetted name (e.g. <em>Tanda Dam Reservoir</em>).</li>
        <li><strong>Type:</strong> Select from <em>Dam / Reservoir</em>, <em>River</em>, <em>Stream</em>, <em>Canal</em>, <em>Headworks</em>, or <em>Other</em>.</li>
        <li><strong>Trout Classification:</strong> Select <em>Trout</em>, <em>Non-trout</em>, <em>Mixed</em>, or <em>Unknown</em>.</li>
        <li><strong>Coordinates:</strong> Input precise Latitude (e.g. <code>33.5678</code>) and Longitude (e.g. <code>71.4567</code>).</li>
        <li><strong>E-Licence Status:</strong> Set <em>Open for licensing</em> to <strong>Yes</strong> to allow citizens to apply for permits here.</li>
        <li><strong>Species &amp; Lease Notes:</strong> Input notable fish species (e.g. <em>Mahseer, Rohu, Mori</em>) and lease restrictions.</li>
        <li><strong>Cover Photo:</strong> Upload high-resolution landscape photo for public catalogue display.</li>
        <li><strong>Active Switch:</strong> Ensure <em>Active</em> toggle is enabled.</li>
      </ul>
    </li>
    <li>Click <strong>Save water body</strong>.</li>
  </ol>
</div>

<div class="step-box">
  <div class="step-number">PROCEDURE 3.4-B</div>
  <div class="step-title">Closing a Water Body for Conservation or Maintenance</div>
  <ol>
    <li>From the Water Bodies list, locate the target reservoir and click <strong>Edit</strong>.</li>
    <li>Under <strong>Licensing Status</strong>, select <strong>Closed (No e-licence)</strong> or uncheck the <em>Active</em> toggle.</li>
    <li>In <strong>Species / Management Notes</strong>, add the justification (e.g. <em>"Temporarily closed due to flood rehabilitation / fish stocking operations."</em>).</li>
    <li>Click <strong>Save changes</strong>. Citizens will immediately see the water body marked as ineligible for e-licence.</li>
  </ol>
</div>

<h3>3.5 How to Configure Licence Categories &amp; Duration Rules</h3>
<p>
  To maintain legal angling categories, bag limits, and statutory fee tariffs (<code>/admin/categories</code>):
</p>

<div class="step-box">
  <div class="step-number">PROCEDURE 3.5</div>
  <div class="step-title">Creating or Updating a Licence Category</div>
  <ol>
    <li>In the sidebar, navigate to <strong>Licence categories</strong> (<code>/admin/categories</code>).</li>
    <li>To add a new permit type, click <strong>Add category</strong>. To adjust existing fees, click <strong>Edit</strong> beside the target category.</li>
    <li>Configure the category fields:
      <ul>
        <li><strong>Category Code:</strong> Unique uppercase identifier (e.g. <code>SWAT-TROUT-DAILY</code>).</li>
        <li><strong>Category Name:</strong> Formal descriptive title (e.g. <em>Swat Valley Daily Trout Angling Permit</em>).</li>
        <li><strong>Duration Type:</strong> Select <code>daily</code>, <code>weekly</code>, <code>monthly</code>, <code>seasonal</code>, or <code>custom</code>.</li>
        <li><strong>Duration Days:</strong> If duration type is daily/weekly/monthly, enter validity days (1 for daily, 7 for weekly, 30 for monthly).</li>
        <li><strong>Fee Amount (PKR):</strong> Input statutory fee in Pakistani Rupees (e.g. <code>1000.00</code>).</li>
        <li><strong>Daily Catch / Bag Limit:</strong> Enter the maximum number of fish an angler is legally permitted to retain per day (e.g. <code>6</code>).</li>
        <li><strong>Expiry Rule:</strong>
          <br>• Select <code>from_start</code> if validity begins on fishing start date and runs for the duration days.
          <br>• Select <code>fixed_date</code> or <code>season_end</code> if the permit must expire on a fixed calendar date (e.g. June 30).
        </li>
        <li><strong>Fixed Expiry Month-Day:</strong> If fixed expiry rule is chosen, input month-day format: <code>06-30</code>.</li>
        <li><strong>Special Instructions &amp; Conditions:</strong> Input legal terms printed on card (e.g. <em>"Valid only from sunrise to sunset. Spinning and fly fishing allowed. Live bait strictly prohibited under KP Fisheries Act."</em>).</li>
        <li><strong>Active Switch:</strong> Toggle to activate or suspend category.</li>
      </ul>
    </li>
    <li>Click <strong>Save category</strong> to commit changes.</li>
  </ol>
</div>

<div class="callout callout-warning">
  <strong>Tariff Update Caution:</strong> Updating a category's fee tariff applies exclusively to new applications submitted after the update. Previously submitted or pending applications preserve their fee snapshot taken at the time of submission.
</div>

<h3>3.6 How to Process &amp; Resolve Violation Incident Reports</h3>
<p>
  When illegal fishing or poaching incidents are reported (<code>/admin/violations</code>), staff must investigate and update cases:
</p>

<div class="step-box">
  <div class="step-number">PROCEDURE 3.6</div>
  <div class="step-title">Investigating &amp; Closing a Violation Report</div>
  <ol>
    <li>In the sidebar, click <strong>Violations</strong> (<code>/admin/violations</code>).</li>
    <li>Filter records by status: <code>pending</code> or <code>under_investigation</code>.</li>
    <li>Click <strong>View</strong> on the target violation tracking number (e.g. <code>VIO-2026-00014</code>).</li>
    <li>Inspect incident facts:
      <ul>
        <li><strong>District &amp; Water Body:</strong> Geographic area of the alleged infraction.</li>
        <li><strong>Date &amp; Time Occurred:</strong> Incident timestamp.</li>
        <li><strong>Reporter Info:</strong> Informant identity or "Anonymous" designation.</li>
        <li><strong>Description:</strong> Narrative of illegal method (e.g. <em>"Use of electric generator shocker in Kumrat River"</em>).</li>
        <li><strong>Evidence Photos:</strong> Click photo thumbnails to inspect full-resolution evidence.</li>
        <li><strong>Map Coordinates:</strong> Click the blue <strong>Map</strong> link to open Google Maps directly centered on the incident pin.</li>
      </ul>
    </li>
    <li><strong>Assigning &amp; Transitioning Case Status (Right Column):</strong>
      <ul>
        <li>Under <strong>Update status</strong>, select status:
          <br>• <code>under_investigation</code>: When dispatching field enforcement inspectors to the scene.
          <br>• <code>resolved</code>: When raid, seizure, fine, or FIR has been successfully executed.
        </li>
        <li>In the <strong>Resolution notes</strong> textarea, enter comprehensive departmental case notes:
          <br><em>Example: "Raid conducted by Sub-Inspector Fisheries on 14-Sep-2026. Two illegal gill nets and one 12V battery generator seized. Offender fined PKR 25,000 under Section 12 of KP Fisheries Rules. Seized gear deposited in District Malkhana."</em>
        </li>
      </ul>
    </li>
    <li>Click the green <strong>Save</strong> button. The report status updates and logs your user account as the closing officer.</li>
  </ol>
</div>

<h3>3.7 How to Manage Fisheries Offices</h3>
<p>
  To maintain departmental facilities and regional office directories (<code>/admin/offices</code>):
</p>

<div class="step-box">
  <div class="step-number">PROCEDURE 3.7</div>
  <div class="step-title">Creating or Updating an Office Facility</div>
  <ol>
    <li>In the sidebar, click <strong>Offices</strong> (<code>/admin/offices</code>).</li>
    <li>To register a new office, click <strong>Add office</strong>. To update an existing office, click <strong>Edit</strong>.</li>
    <li>Complete the required fields:
      <ul>
        <li><strong>District:</strong> Select the geographic district where the office is physically situated.</li>
        <li><strong>Office Name:</strong> Official designation (e.g. <em>Office of the District Fisheries Officer Swabi</em>). Must be unique within the selected district.</li>
        <li><strong>Official Telephone:</strong> Landline or mobile contact number.</li>
        <li><strong>Official Email:</strong> Departmental email address.</li>
        <li><strong>Postal Address:</strong> Physical address of the office or hatchery.</li>
        <li><strong>Active Status:</strong> Ensure checked.</li>
      </ul>
    </li>
    <li>Click <strong>Save changes</strong>.</li>
  </ol>
</div>

<h3>3.8 How to Onboard Staff Users &amp; Configure District Scopes</h3>
<p>
  Super Admins and authorized managers can provision new staff accounts and define their territorial boundaries (<code>/admin/users</code>):
</p>

<div class="step-box">
  <div class="step-number">PROCEDURE 3.8</div>
  <div class="step-title">Complete Staff Onboarding &amp; Scope Assignment</div>
  <ol>
    <li>In the sidebar, click <strong>User management</strong> (<code>/admin/users</code>).</li>
    <li>Click the green <strong>Add staff user</strong> button (<code>/admin/users/create</code>).</li>
    <li><strong>Left Column — Personal &amp; Account Details:</strong>
      <ul>
        <li>Enter <strong>Full Name</strong>, official <strong>Email</strong>, and <strong>Mobile</strong> number.</li>
        <li>Enter <strong>CNIC</strong> (formatted <code>12345-1234567-1</code>) and <strong>Father's Name</strong>.</li>
        <li>Input <strong>Date of Birth</strong>, <strong>Gender</strong>, <strong>Address</strong>, and <strong>Emergency Contact</strong>.</li>
        <li>In <strong>Designation</strong>, enter official civil service title (e.g. <em>District Fisheries Officer Swat</em>).</li>
        <li>In <strong>Assigned Primary District</strong>, select the officer's principal district.</li>
        <li>In <strong>Account Type</strong>, choose <code>admin</code> (standard staff), <code>executive</code> (oversight), or <code>super_admin</code> (if you have super admin privileges).</li>
        <li>Enter and confirm a secure <strong>Password</strong> (minimum 8 characters).</li>
        <li>Ensure <strong>Active</strong> toggle is enabled.</li>
      </ul>
    </li>
    <li style="margin-top: 4pt;"><strong>Right Column — District Scoping &amp; Office Mapping:</strong>
      <ul>
        <li><strong>All Districts Switch:</strong>
          <br>• If the officer requires province-wide jurisdiction, toggle <strong>"All districts"</strong> to <strong>ON</strong>.
          <br>• If the officer is district-bound, leave it <strong>OFF</strong>. A multi-select checklist of all 36 districts appears. Check the specific district(s) the officer is authorized to oversee.
        </li>
        <li><strong>Assigned Offices:</strong> Once districts are selected, the office section displays facilities grouped by district. Check the specific office(s) where the staff member operates, or use the <em>"Select All [District] Offices"</em> shortcut.</li>
      </ul>
    </li>
    <li style="margin-top: 4pt;"><strong>Right Column — Group &amp; Role Assignments:</strong>
      <ul>
        <li>Scroll down to <strong>Assigned Groups &amp; Roles</strong>.</li>
        <li>Check one or more applicable groups (e.g. <em>District Officer</em> or <em>Office / Assistant</em>).</li>
        <li>The system displays a badge summary of configured module actions for each selected group.</li>
      </ul>
    </li>
    <li style="margin-top: 4pt;">Click <strong>Create user</strong> to commit the account. The staff member can now log in immediately.</li>
  </ol>
</div>

<h3>3.9 How to Create Groups &amp; Set Granular Permissions</h3>
<p>
  To create customized access roles with fine-grained operational permissions (<code>/admin/groups</code>):
</p>

<div class="step-box">
  <div class="step-number">PROCEDURE 3.9</div>
  <div class="step-title">Configuring a Permission Group Matrix</div>
  <ol>
    <li>Navigate to <strong>Groups &amp; Roles</strong> in the sidebar (<code>/admin/groups</code>).</li>
    <li>Click the green <strong>Create group</strong> button (<code>/admin/groups/create</code>).</li>
    <li>In the <strong>Group Name</strong> field, enter a clear departmental title (e.g. <em>Trout Belt Enforcement Inspector</em>).</li>
    <li>In the <strong>Description</strong> field, summarize the operational scope and duties.</li>
    <li><strong>Permission Blueprint Shortcuts:</strong>
      <br>Above the matrix table, click any preset button (<em>DG / Provincial</em>, <em>District Officer</em>, <em>Office / Assistant</em>, <em>Field Officer</em>) to automatically populate standard permission checkboxes.
    </li>
    <li><strong>Custom Matrix Configuration:</strong>
      <br>Independently check or uncheck individual permission boxes across all 15 modules (View, Create, Edit, Delete, Status, Approve). Enabling any action automatically enables View permission.
    </li>
    <li>Click <strong>Create group</strong> (or <strong>Save changes</strong>). Any staff user assigned to this group immediately inherits these privileges.</li>
  </ol>
</div>

<h3>3.10 How to Execute the 4-Step Policy Setup Wizard</h3>
<p>
  Super Admins and Provincial Policy Executives execute the Policy Setup Console (<code>/admin/setup</code>) 
  to configure global rules before launching annual licensing:
</p>

<div class="step-box">
  <div class="step-number">PROCEDURE 3.10</div>
  <div class="step-title">Executing the 4-Step Policy Wizard</div>
  
  <h4 style="color: #0d5c3a; margin-top: 4pt;">Step 1: Licence Fees, 30 June Rule &amp; Closed Breeding Season (/admin/setup/fees)</h4>
  <ol>
    <li>Navigate to <strong>Policy setup</strong> in sidebar. Click on <strong>Step 1: Fees &amp; seasons</strong>.</li>
    <li>In the <strong>Licence Fees</strong> table, verify or update the base fee (in PKR) for every active category.</li>
    <li>In <strong>Seasonal Expiry</strong>, verify the month-day date for annual fixed expiry (defaults to <code>06-30</code> for June 30).</li>
    <li>In <strong>Closed / breeding season</strong>, configure the statutory spawning closure window:
      <ul>
        <li><strong>Start Month &amp; Day:</strong> Input starting date (e.g. Month: <code>6</code>, Day: <code>1</code> for June 1).</li>
        <li><strong>End Month &amp; Day:</strong> Input concluding date (e.g. Month: <code>7</code>, Day: <code>31</code> for July 31).</li>
        <li><strong>Closed Reason:</strong> Descriptive legal note (e.g. <em>"Annual statutory spawning and breeding closed season under KP Fisheries Act"</em>).</li>
        <li>Check the <strong>Active</strong> box to enforce the closure.</li>
      </ul>
    </li>
    <li>Check the confirmation checkbox: <strong>"I confirm the seasonal expiry and category fees above."</strong></li>
    <li>Click <strong>Save Step 1 &amp; continue</strong>.</li>
  </ol>

  <h4 style="color: #0d5c3a; margin-top: 6pt;">Step 2: Water Body Eligibility (/admin/setup/eligibility)</h4>
  <ol>
    <li>Review the catalog summary counters (Open, Closed, Undecided, Total).</li>
    <li>Ensure the <strong>"Require explicit e-licence eligibility"</strong> toggle is enabled (<span class="badge badge-success">Recommended</span>).</li>
    <li>For each water body in the list, set eligibility (Open or Closed).</li>
    <li>Check the confirmation box: <strong>"I confirm the water body e-licence eligibility marked above."</strong></li>
    <li>Click <strong>Save Step 2 &amp; continue</strong>.</li>
  </ol>

  <h4 style="color: #0d5c3a; margin-top: 6pt;">Step 3: Permission Templates Blueprint (/admin/setup/templates)</h4>
  <ol>
    <li>Review the active staff distribution across the 4 standard blueprints (DG, DFO, Assistant, Field).</li>
    <li>Check the confirmation box: <strong>"I confirm using these permission templates when creating staff."</strong></li>
    <li>Click <strong>Save Step 3 &amp; continue</strong>.</li>
  </ol>

  <h4 style="color: #0d5c3a; margin-top: 6pt;">Step 4: Public QR Privacy Shield (/admin/setup/qr)</h4>
  <ol>
    <li>In <strong>Holder display</strong>, select <code>Masked (recommended)</code> or <code>Full name / CNIC</code>.</li>
    <li>Toggle <strong>"Show CNIC on public verify page"</strong> (recommend leaving OFF for citizen privacy).</li>
    <li>Inspect the <strong>Live preview card</strong> on the right to verify visual appearance.</li>
    <li>Check the confirmation box: <strong>"I confirm this public QR privacy mode for the pilot."</strong></li>
    <li>Click <strong>Save &amp; finish setup</strong>. All policy configurations are now live province-wide.</li>
  </ol>
</div>

<h3>3.11 How to Generate &amp; Print Audit Reports</h3>
<p>
  To produce official statistical documentation or financial audit records (<code>/admin/reports</code>):
</p>

<div class="step-box">
  <div class="step-number">PROCEDURE 3.11</div>
  <div class="step-title">Generating CSV Spreadsheets &amp; Printable Summaries</div>
  <ol>
    <li>In the sidebar, click <strong>Reports</strong> (<code>/admin/reports</code>).</li>
    <li>In the <strong>Report</strong> dropdown, select dataset: <code>Licence applications</code>, <code>Issued licences</code>, or <code>Violation reports</code>.</li>
    <li>(Optional) In <strong>From</strong> and <strong>To</strong> date fields, specify the reporting period.</li>
    <li>(Optional) In <strong>Status</strong>, enter a specific state (e.g. <code>approved</code> or <code>resolved</code>).</li>
    <li>In <strong>Format</strong>, choose <code>CSV / Excel</code> or <code>Printable PDF</code>.</li>
    <li>Click <strong>Generate report</strong>.</li>
    <li><em>CSV Result:</em> Instant UTF-8 BOM spreadsheet download begins.</li>
    <li><em>Printable PDF Result:</em> Opens high-contrast print layout in a new tab. Press <code>Ctrl + P</code> to print or save.</li>
  </ol>
</div>

<h3>3.12 How to Verify Licences via Mobile QR Scanning</h3>
<p>
  Field enforcement teams and checkpoint inspectors verify permits on water bodies using any camera-equipped device:
</p>

<div class="step-box">
  <div class="step-number">PROCEDURE 3.12</div>
  <div class="step-title">Field E-Licence Verification Workflow</div>
  <ol>
    <li>Instruct the angler to present their digital e-licence on their smartphone screen or their physical paper card.</li>
    <li>Open the camera app on your smartphone or scanning device and point it at the printed QR code.</li>
    <li>Tap the secure verification link: <code>https://[portal-domain]/verify/licence/[qr_token]</code>.</li>
    <li>The verification screen loads:
      <ul>
        <li><span class="badge badge-success" style="font-size: 8pt;">VALID LICENCE</span>: Indicated by green header badge.</li>
        <li>Verify <strong>Licence No</strong> (e.g. <code>RFL-2026-00045</code>).</li>
        <li>Verify <strong>Permit Holder</strong> name against physical CNIC card.</li>
        <li>Verify <strong>Water Body Authorization</strong> (permit valid only for designated water body).</li>
        <li>Verify <strong>Validity Period:</strong> Confirm current date falls between <em>Valid From</em> and <em>Expiry Date</em>.</li>
        <li>Verify <strong>Category &amp; Daily Bag Limit:</strong> Confirm catch does not exceed printed limit.</li>
      </ul>
    </li>
    <li>If expired, revoked, or non-existent, the screen displays a red alert: <span class="badge badge-danger" style="font-size: 8pt;">EXPIRED / INVALID LICENCE</span>. Take immediate enforcement action.</li>
  </ol>
</div>

<div class="page-break-before"></div>

<!-- ==================== SECTION 4 ==================== -->
<div class="running-header">
  <span>KP Fisheries RFLMS — Admin User Manual</span>
  <span>Section 4: Rules &amp; Business Logic</span>
</div>

<h2>4. System Rules, Business Logic &amp; Constraints</h2>
<p>
  Administrators must understand the underlying automated business logic enforced by the RFLMS core engine:
</p>

<h3>4.1 Closed Breeding Season Enforcement Rules</h3>
<ul>
  <li><strong>Statutory Basis:</strong> Under the Khyber Pakhtunkhwa Fisheries Act and Rules, fishing during peak spawning periods is strictly prohibited to protect fish stocks from depletion.</li>
  <li><strong>Application Date Interception:</strong> When a citizen or walk-in operator selects a <em>Fishing Start Date</em>, the system executes <code>LicensingPolicyService::isClosedOn()</code>.</li>
  <li><strong>Rejection Mechanism:</strong> If the selected date falls within the configured start and end month/day (e.g. June 1 to July 31), the form throws an immediate validation exception:
    <div class="callout callout-danger" style="margin: 4pt 0;">
      <em>"Validation Error: Fishing is not allowed on this date (Breeding / closed season)."</em>
    </div>
    Submission is blocked at the database level.
  </li>
</ul>

<h3>4.2 The 30 June Seasonal Expiry Rule</h3>
<ul>
  <li><strong>Fiscal &amp; Biological Alignment:</strong> In Khyber Pakhtunkhwa, all annual/seasonal recreational fishing licences expire on <strong>June 30</strong> of each year, coinciding with the close of the government financial year and the commencement of summer spawning.</li>
  <li><strong>Automated Date Calculation:</strong>
    <ul>
      <li>If an angler purchases a seasonal permit on <em>September 1, 2026</em>, the system automatically sets the expiration date to <em>June 30, 2027</em>.</li>
      <li>If an angler purchases a seasonal permit on <em>May 15, 2027</em>, the licence still terminates on <em>June 30, 2027</em> (it does NOT extend into the subsequent fiscal cycle).</li>
    </ul>
  </li>
  <li><strong>Fixed Expiry Rule:</strong> Governed by the <code>fixed_expiry_month_day</code> setting (<code>06-30</code>) configured in Policy Setup Step 1.</li>
</ul>

<h3>4.3 District Boundary &amp; Jurisdiction Scoping Rules</h3>
<ul>
  <li><strong>Scope Enforcement:</strong> Operational staff users only possess database read/write visibility over water bodies, offices, applications, and violation reports situated within their <code>scopedDistrictIds()</code>.</li>
  <li><strong>Super Admin Exemption:</strong> Only users with the <code>super_admin</code> account type or users with the <code>all_districts</code> flag explicitly enabled bypass district scoping.</li>
  <li><strong>Direct URL Tampering Shield:</strong> If an officer attempts to manipulate browser URL IDs to access an out-of-district record, the application aborts immediately with HTTP Status 403 Forbidden.</li>
</ul>

<h3>4.4 Payment Reconciliation Rules</h3>
<ul>
  <li><strong>Counter Cash:</strong> Immediate ledger entry created with reference <code>COUNTER-CASH-[timestamp]</code>. Auto-approval permissible for on-the-spot counter service.</li>
  <li><strong>Bank Transfer &amp; Deposit Slips:</strong> Requires officer inspection of uploaded file path. Approval must be withheld until bank scroll verification confirms receipt in the provincial account.</li>
  <li><strong>1Bill / PSID:</strong> Generates unique electronic voucher code (e.g. <code>PSID-WALKIN-XXXXXXXXXX</code>) reconcilable via banking switches.</li>
</ul>

<div class="page-break-before"></div>

<!-- ==================== SECTION 5 ==================== -->
<div class="running-header">
  <span>KP Fisheries RFLMS — Admin User Manual</span>
  <span>Section 5: Troubleshooting &amp; FAQ</span>
</div>

<h2>5. Troubleshooting &amp; Frequently Asked Questions (FAQ)</h2>

<dl class="key-value-list">
  <dt>Q1: Why does a water body not appear in the dropdown when an angler tries to apply online?</dt>
  <dd>
    <strong>Resolution:</strong> Check two settings in the Admin Portal:
    <br>1. Navigate to <code>/admin/reservoirs</code>, locate the water body, and ensure the <strong>Active</strong> toggle is checked.
    <br>2. Navigate to Policy Setup Step 2 (<code>/admin/setup/eligibility</code>) and verify that the water body is set to <strong>Open for licensing</strong>. If set to <em>Closed</em> or <em>Undecided</em>, it is hidden from citizen application menus.
  </dd>

  <dt>Q2: Why does the Walk-in Application throw an error saying "Fishing is not allowed on this date"?</dt>
  <dd>
    <strong>Resolution:</strong> The selected <em>Fishing Start Date</em> falls within the statutory Closed Breeding Season configured in Policy Setup Step 1. Choose a date outside the closed window, or temporarily adjust the breeding season dates in <code>/admin/setup/fees</code> if gazette dates have been officially shifted.
  </dd>

  <dt>Q3: An assistant officer cannot approve applications; the "Approve" button is missing on their screen. Why?</dt>
  <dd>
    <strong>Resolution:</strong> The officer's assigned group lacks the <code>approve</code> permission for the <code>applications</code> module.
    <br>1. Navigate to <code>/admin/groups</code> and edit the officer's group.
    <br>2. In the row for <strong>Licence Applications</strong>, ensure the <strong>Approve</strong> checkbox is checked.
    <br>3. Save the group. The officer must refresh their browser session to see the decision panel.
  </dd>

  <dt>Q4: How can a DFO see applications from an adjacent district during an inter-district operation?</dt>
  <dd>
    <strong>Resolution:</strong> A Super Admin must edit the officer's profile at <code>/admin/users/{{id}}/edit</code> and check the adjacent district in their <strong>District Scope</strong> checklist, then save changes.
  </dd>

  <dt>Q5: When scanning a QR code with a smartphone, the holder's name appears with asterisks (e.g. "A** K**n"). Is this an error?</dt>
  <dd>
    <strong>Resolution:</strong> No, this is the intended behavior of the <strong>Public QR Privacy Shield</strong> (Policy Setup Step 4). It protects citizen privacy against unauthorized public snooping. When authenticated departmental officers scan the QR code from within the mobile officer app, the full unmasked demographic profile is revealed.
  </dd>

  <dt>Q6: An applicant made a typo in their CNIC during online registration. How can an admin fix it?</dt>
  <dd>
    <strong>Resolution:</strong> In the application review screen, click <strong>Request info</strong> and instruct the citizen to update their profile, or navigate to <code>/admin/users</code>, locate the citizen's account, and correct the CNIC in their profile dossier.
  </dd>
</dl>

<div class="callout callout-success" style="margin-top: 12pt;">
  <strong>Departmental Support &amp; Technical Escalation:</strong><br>
  For technical system issues, database anomalies, or infrastructure support, contact the IT Directorate at:<br>
  <strong>Email:</strong> <code>support.fisheries@kp.gov.pk</code> | <strong>Helpdesk Hotline:</strong> 091-9210000<br>
  Directorate General of Fisheries, Government of Khyber Pakhtunkhwa, Peshawar.
</div>

</body>
</html>
"""
    return html

def find_page_numbers(pdf_path, markers):
    res_pages = {}
    
    info_out = subprocess.run(["pdfinfo", pdf_path], capture_output=True, text=True).stdout
    m = re.search(r"Pages:\s+(\d+)", info_out)
    total_pages = int(m.group(1)) if m else 25
    
    for page in range(3, total_pages + 1):
        txt = subprocess.run(
            ["pdftotext", "-f", str(page), "-l", str(page), pdf_path, "-"],
            capture_output=True, text=True
        ).stdout
        
        for key, marker_txt in markers.items():
            if key not in res_pages and marker_txt in txt:
                res_pages[key] = str(page)
                    
    return res_pages

def main():
    print("KP Fisheries RFLMS Admin User Manual Dual-Pass Builder")
    print("-" * 55)
    
    markers = {
        "sec1": "1. System Architecture & Security Access Model",
        "sec1_1": "1.1 Introduction to the E-Licensing System",
        "sec1_2": "1.2 Accessing the Admin Portal",
        "sec1_3": "1.3 Staff Role Hierarchy",
        "sec1_4": "1.4 District & Office Scoping Architecture",
        "sec1_5": "1.5 Role-Based Access Control (RBAC)",
        "sec2": "2. What Admin Can Do (System Capabilities Matrix)",
        "sec2_1": "2.1 Operational & Analytics Dashboard",
        "sec2_2": "2.2 Licence Applications Lifecycle Management",
        "sec2_3": "2.3 Walk-in Counter Licensing & Instant Issuance",
        "sec2_4": "2.4 Water Bodies & Reservoir Master Catalog",
        "sec2_5": "2.5 Licence Categories, Bag Limits & Fee Tariffs",
        "sec2_6": "2.6 Anti-Poaching & Violation Reports Management",
        "sec2_7": "2.7 Fisheries Offices Directory Management",
        "sec2_8": "2.8 Staff User Provisioning & Account Control",
        "sec2_9": "2.9 Groups & Custom Role Management",
        "sec2_10": "2.10 Four-Step Policy Setup & Governance Wizard",
        "sec2_11": "2.11 Audit Reporting & Data Exports",
        "sec2_12": "2.12 Public QR Code Verification & Privacy Shield",
        "sec3": "3. How Admin Can Do It (Step-by-Step Operating Procedures)",
        "sec3_1": "3.1 How to Log In & Manage Portal Sessions",
        "sec3_2": "3.2 How to Review & Decide on Citizen Applications",
        "sec3_3": "3.3 How to Issue Walk-in Counter Licences On-The-Spot",
        "sec3_4": "3.4 How to Manage Water Bodies & Geolocation",
        "sec3_5": "3.5 How to Configure Licence Categories & Duration Rules",
        "sec3_6": "3.6 How to Process & Resolve Violation Incident Reports",
        "sec3_7": "3.7 How to Manage Fisheries Offices",
        "sec3_8": "3.8 How to Onboard Staff Users & Configure District Scopes",
        "sec3_9": "3.9 How to Create Groups & Set Granular Permissions",
        "sec3_10": "3.10 How to Execute the 4-Step Policy Setup Wizard",
        "sec3_11": "3.11 How to Generate & Print Audit Reports",
        "sec3_12": "3.12 How to Verify Licences via Mobile QR Scanning",
        "sec4": "4. System Rules, Business Logic & Constraints",
        "sec4_1": "4.1 Closed Breeding Season Enforcement Rules",
        "sec4_2": "4.2 The 30 June Seasonal Expiry Rule",
        "sec4_3": "4.3 District Boundary & Jurisdiction Scoping Rules",
        "sec4_4": "4.4 Payment Reconciliation Rules",
        "sec5": "5. Troubleshooting & Frequently Asked Questions (FAQ)"
    }
    
    html_path = "/var/www/kp-fisheries-e-license/docs/KP_Fisheries_RFLMS_Admin_User_Manual.html"
    pdf_path = "/var/www/kp-fisheries-e-license/docs/KP_Fisheries_RFLMS_Admin_User_Manual.pdf"
    public_pdf_path = "/var/www/kp-fisheries-e-license/public/docs/KP_Fisheries_RFLMS_Admin_User_Manual.pdf"
    
    # PASS 1: Build draft HTML & render draft PDF
    print("[*] PASS 1: Rendering initial draft...")
    draft_html = build_manual_html()
    with open(html_path, "w", encoding="utf-8") as f:
        f.write(draft_html)
        
    subprocess.run([
        "google-chrome", "--headless", "--disable-gpu", "--no-sandbox",
        "--no-pdf-header-footer", f"--print-to-pdf={pdf_path}", html_path
    ], check=True)
    
    # Scan page numbers
    print("[*] PASS 1: Scanning exact page numbers of sections from Page 3 onwards...")
    detected_pages = find_page_numbers(pdf_path, markers)
    for k, v in detected_pages.items():
        print(f"    {k} -> Page {v}")
        
    # Fill any fallback
    for k in markers:
        if k not in detected_pages:
            detected_pages[k] = "..."
            
    # PASS 2: Rebuild HTML with verified page numbers
    print("[*] PASS 2: Injecting accurate page numbers into Table of Contents...")
    final_html = build_manual_html(detected_pages)
    with open(html_path, "w", encoding="utf-8") as f:
        f.write(final_html)
        
    print("[*] PASS 2: Rendering final production PDF...")
    subprocess.run([
        "google-chrome", "--headless", "--disable-gpu", "--no-sandbox",
        "--no-pdf-header-footer", f"--print-to-pdf={pdf_path}", html_path
    ], check=True)
    
    # Copy to public web access
    subprocess.run(["cp", pdf_path, public_pdf_path], check=True)
    
    # Final check
    pdf_size = os.path.getsize(pdf_path)
    info_out = subprocess.run(["pdfinfo", pdf_path], capture_output=True, text=True).stdout
    m = re.search(r"Pages:\s+(\d+)", info_out)
    total_pages = m.group(1) if m else "N/A"
    
    print("-" * 55)
    print(f"[SUCCESS] Final PDF Generated: {pdf_path}")
    print(f"[SUCCESS] Total Pages: {total_pages}")
    print(f"[SUCCESS] File Size: {pdf_size} bytes ({pdf_size / (1024*1024):.2f} MB)")
    print(f"[SUCCESS] Public Download URL: /docs/KP_Fisheries_RFLMS_Admin_User_Manual.pdf")

if __name__ == "__main__":
    main()
