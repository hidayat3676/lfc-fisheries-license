#!/usr/bin/env python3
"""
KP Fisheries E-License System (RFLMS)
Public Site & Citizen User Manual PDF Generator
Generates a comprehensive, executive-grade PDF manual covering:
1. What Citizens & Anglers Can Do (Public Services, Online Permitting, Catalog, Whistleblowing, Verification)
2. How Citizens Can Do It (Procedural Step-by-Step Guides, Registration, OTP, Pattern Lock, E-Licensing, Payments, Reporting)
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

def build_public_manual_html(toc_pages=None):
    logo_b64 = get_base64_image("/var/www/kp-fisheries-e-license/public/images/logo-2.png")
    
    if toc_pages is None:
        toc_pages = {k: "..." for k in [
            "sec1", "sec1_1", "sec1_2", "sec1_3", "sec1_4",
            "sec2", "sec2_1", "sec2_2", "sec2_3", "sec2_4", "sec2_5", "sec2_6", "sec2_7", "sec2_8",
            "sec3", "sec3_1", "sec3_2", "sec3_3", "sec3_4", "sec3_5", "sec3_6", "sec3_7", "sec3_8", "sec3_9", "sec3_10",
            "sec4", "sec4_1", "sec4_2", "sec4_3", "sec4_4",
            "sec5"
        ]}
    
    html = f"""<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>KP Fisheries RFLMS - Citizen &amp; Public User Manual</title>
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
    <div class="subtitle">Official Public Portal &amp; Citizen E-Licensing User Guide</div>
  </div>

  <div class="cover-desc">
    A comprehensive guide for citizens, recreational anglers, and the general public explaining 
    how to register an account, complete CNIC profiles, explore public fishing waters, apply for digital e-licences, 
    make online fee payments, download 3D licence cards, and report illegal fishing activities.
  </div>

  <div class="cover-metadata">
    <table>
      <tr><td class="label">Document Ref:</td><td>RFLMS-MAN-PUB-v1.0</td></tr>
      <tr><td class="label">Portal Access:</td><td><code>https://[portal-domain]/</code></td></tr>
      <tr><td class="label">Audience:</td><td>Recreational Anglers, Tourists, Fishing Clubs &amp; General Public</td></tr>
      <tr><td class="label">Key Services:</td><td>E-Licence Application, Digital Permit Card, Whistleblower Reporting, QR Verification</td></tr>
      <tr><td class="label">Effective Date:</td><td>September 2026</td></tr>
      <tr><td class="label">Published By:</td><td>Directorate General of Fisheries, Khyber Pakhtunkhwa</td></tr>
    </table>
  </div>

  <div class="cover-footer">
    Government of Khyber Pakhtunkhwa · Directorate General of Fisheries. Information contained herein is official and public.
  </div>
</div>

<!-- ==================== TABLE OF CONTENTS (PAGE 2) ==================== -->
<div class="toc-container">
  <div class="running-header">
    <span>KP Fisheries RFLMS — Citizen User Manual</span>
    <span>Table of Contents</span>
  </div>

  <h2 class="toc-title">Table of Contents</h2>

  <div class="toc-item level-1"><span>1. INTRODUCTION &amp; PUBLIC SERVICES OVERVIEW</span><span class="toc-dots"></span><span>Page {toc_pages['sec1']}</span></div>
  <div class="toc-item level-2"><span>1.1 Welcome to KP Fisheries E-Licensing</span><span class="toc-dots"></span><span>Page {toc_pages['sec1_1']}</span></div>
  <div class="toc-item level-2"><span>1.2 Why Digital E-Licensing? (Benefits to Citizens)</span><span class="toc-dots"></span><span>Page {toc_pages['sec1_2']}</span></div>
  <div class="toc-item level-2"><span>1.3 Public Access Points &amp; Browser Compatibility</span><span class="toc-dots"></span><span>Page {toc_pages['sec1_3']}</span></div>
  <div class="toc-item level-2"><span>1.4 Prerequisites for Applying</span><span class="toc-dots"></span><span>Page {toc_pages['sec1_4']}</span></div>

  <div class="toc-item level-1"><span>2. WHAT CITIZENS &amp; ANGLERS CAN DO (SERVICES &amp; CAPABILITIES)</span><span class="toc-dots"></span><span>Page {toc_pages['sec2']}</span></div>
  <div class="toc-item level-2"><span>2.1 Public Water Bodies Catalog &amp; Discovery</span><span class="toc-dots"></span><span>Page {toc_pages['sec2_1']}</span></div>
  <div class="toc-item level-2"><span>2.2 Online Angler Registration &amp; Secure Verification</span><span class="toc-dots"></span><span>Page {toc_pages['sec2_2']}</span></div>
  <div class="toc-item level-2"><span>2.3 Complete Digital Angler Profile Management</span><span class="toc-dots"></span><span>Page {toc_pages['sec2_3']}</span></div>
  <div class="toc-item level-2"><span>2.4 9-Dot Pattern Lock Account Security</span><span class="toc-dots"></span><span>Page {toc_pages['sec2_4']}</span></div>
  <div class="toc-item level-2"><span>2.5 Applying for E-Licences (Daily, Weekly, Monthly, Seasonal)</span><span class="toc-dots"></span><span>Page {toc_pages['sec2_5']}</span></div>
  <div class="toc-item level-2"><span>2.6 Multiple Flexible Payment Channels (1Bill / Bank Transfer / Deposit)</span><span class="toc-dots"></span><span>Page {toc_pages['sec2_6']}</span></div>
  <div class="toc-item level-2"><span>2.7 Real-Time Application Tracking &amp; Officer Feedback</span><span class="toc-dots"></span><span>Page {toc_pages['sec2_7']}</span></div>
  <div class="toc-item level-2"><span>2.8 Interactive 3D Digital Licence Card &amp; QR Verification</span><span class="toc-dots"></span><span>Page {toc_pages['sec2_8']}</span></div>

  <div class="toc-item level-1"><span>3. HOW CITIZENS CAN DO IT (STEP-BY-STEP OPERATIONAL GUIDE)</span><span class="toc-dots"></span><span>Page {toc_pages['sec3']}</span></div>
  <div class="toc-item level-2"><span>3.1 How to Create an Account with Email OTP Verification</span><span class="toc-dots"></span><span>Page {toc_pages['sec3_1']}</span></div>
  <div class="toc-item level-2"><span>3.2 How to Log In &amp; Use 9-Dot Pattern Lock Verification</span><span class="toc-dots"></span><span>Page {toc_pages['sec3_2']}</span></div>
  <div class="toc-item level-2"><span>3.3 How to Complete Your Angler Profile (Mandatory for E-Licence)</span><span class="toc-dots"></span><span>Page {toc_pages['sec3_3']}</span></div>
  <div class="toc-item level-2"><span>3.4 How to Explore Water Bodies in the Public Catalog</span><span class="toc-dots"></span><span>Page {toc_pages['sec3_4']}</span></div>
  <div class="toc-item level-2"><span>3.5 How to Apply for an E-Licence Online</span><span class="toc-dots"></span><span>Page {toc_pages['sec3_5']}</span></div>
  <div class="toc-item level-2"><span>3.6 How to Pay via 1Bill, Bank Transfer, or Deposit Slip</span><span class="toc-dots"></span><span>Page {toc_pages['sec3_6']}</span></div>
  <div class="toc-item level-2"><span>3.7 How to Track Your Application Status &amp; Respond to Info Requests</span><span class="toc-dots"></span><span>Page {toc_pages['sec3_7']}</span></div>
  <div class="toc-item level-2"><span>3.8 How to View, Flip &amp; Print Your Digital Licence Card</span><span class="toc-dots"></span><span>Page {toc_pages['sec3_8']}</span></div>
  <div class="toc-item level-2"><span>3.9 How to Report Illegal Fishing / Poaching (Whistleblowing)</span><span class="toc-dots"></span><span>Page {toc_pages['sec3_9']}</span></div>
  <div class="toc-item level-2"><span>3.10 How to Verify Any E-Licence via Public QR Scan</span><span class="toc-dots"></span><span>Page {toc_pages['sec3_10']}</span></div>

  <div class="toc-item level-1"><span>4. LEGAL ANGLING RULES, BAG LIMITS &amp; CONSERVATION</span><span class="toc-dots"></span><span>Page {toc_pages['sec4']}</span></div>
  <div class="toc-item level-2"><span>4.1 Statutory Closed Breeding Seasons (Spawning Protection)</span><span class="toc-dots"></span><span>Page {toc_pages['sec4_1']}</span></div>
  <div class="toc-item level-2"><span>4.2 Daily Bag Limits (Catch Quotas) by Fishery Zone</span><span class="toc-dots"></span><span>Page {toc_pages['sec4_2']}</span></div>
  <div class="toc-item level-2"><span>4.3 Prohibited Fishing Methods (Criminal Offenses)</span><span class="toc-dots"></span><span>Page {toc_pages['sec4_3']}</span></div>
  <div class="toc-item level-2"><span>4.4 Single Active Licence Policy per Water Body</span><span class="toc-dots"></span><span>Page {toc_pages['sec4_4']}</span></div>

  <div class="toc-item level-1"><span>5. CITIZEN TROUBLESHOOTING &amp; FREQUENTLY ASKED QUESTIONS (FAQ)</span><span class="toc-dots"></span><span>Page {toc_pages['sec5']}</span></div>
</div>

<!-- ==================== SECTION 1 ==================== -->
<div class="running-header">
  <span>KP Fisheries RFLMS — Citizen User Manual</span>
  <span>Section 1: Introduction &amp; Overview</span>
</div>

<h2>1. Introduction &amp; Public Services Overview</h2>

<h3>1.1 Welcome to KP Fisheries E-Licensing</h3>
<p>
  The Directorate General of Fisheries, Government of Khyber Pakhtunkhwa, welcomes all recreational anglers, sportfishing 
  enthusiasts, and nature tourists to the <strong>Recreational Fisheries Licensing &amp; Management System (RFLMS)</strong>. 
  Khyber Pakhtunkhwa is renowned across South Asia for its pristine freshwater ecosystems, encompassing world-famous snow-fed 
  trout rivers in Swat, Kumrat, and Kaghan, as well as expansive reservoirs like Tarbela, Khanpur, and Tanda dams teeming with Mahseer and Carps.
</p>
<p>
  To modernize departmental interactions, eliminate administrative bottlenecks, and protect aquatic biodiversity, 
  the Government has transitioned from outdated manual paper challans to a 100% digital, mobile-friendly licensing platform.
</p>

<h3>1.2 Why Digital E-Licensing? (Benefits to Citizens)</h3>
<ul>
  <li><strong>Apply from Anywhere, Anytime:</strong> Apply for permits 24/7 from the comfort of your home or mobile phone without visiting government offices.</li>
  <li><strong>Instant Digital Delivery:</strong> Once approved, your official digital permit with QR verification is available on your dashboard immediately.</li>
  <li><strong>No Physical Bank Queues:</strong> Pay seamlessly through online banking, 1Bill / PSID, mobile wallets (EasyPaisa/JazzCash), or standard bank deposit slips.</li>
  <li><strong>Interactive 3D Licence Card:</strong> Access a tamper-proof digital card complete with your photograph, authorized fishing dates, bag limits, and security hologram.</li>
  <li><strong>Public Safety &amp; Legal Protection:</strong> Carry your digital permit on your phone to prevent disputes with field enforcement teams.</li>
</ul>

<h3>1.3 Public Access Points &amp; Browser Compatibility</h3>
<table class="data-table">
  <thead>
    <tr>
      <th style="width: 30%;">Channel / Endpoint</th>
      <th style="width: 70%;">Access Details &amp; Compatibility</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><strong>Public Web Portal</strong></td>
      <td><code>https://[portal-domain]/</code> — Fully responsive across desktop browsers (Chrome, Edge, Firefox, Safari) and mobile browsers on Android and iOS.</td>
    </tr>
    <tr>
      <td><strong>Citizen Account Area</strong></td>
      <td><code>https://[portal-domain]/account</code> — Dedicated self-service hub for managing profile, tracking applications, and viewing active permits.</td>
    </tr>
    <tr>
      <td><strong>Public QR Verification</strong></td>
      <td><code>https://[portal-domain]/verify/licence/[qr_token]</code> — Instant scanning via smartphone camera.</td>
    </tr>
    <tr>
      <td><strong>Whistleblower Portal</strong></td>
      <td><code>https://[portal-domain]/report-violation</code> — Open public portal to report illegal fishing without requiring login.</td>
    </tr>
  </tbody>
</table>

<h3>1.4 Prerequisites for Applying</h3>
<p>Before initiating an application, ensure you have the following ready:</p>
<ol>
  <li><strong>Valid Pakistani CNIC:</strong> Standard 13-digit Computerized National Identity Card number (e.g. <code>12345-1234567-1</code>).</li>
  <li><strong>Active Email Address:</strong> Used for registration and receiving one-time passcodes (OTP).</li>
  <li><strong>Active Mobile Number:</strong> For status updates and emergency contact.</li>
  <li><strong>Digital Portrait Photo:</strong> Clear face photo (JPEG/PNG, max 2MB) to be printed on your licence card.</li>
  <li><strong>Payment Proof:</strong> Screenshot or photo of online bank transfer, bank deposit slip, or 1Bill receipt.</li>
</ol>

<div class="page-break-before"></div>

<!-- ==================== SECTION 2 ==================== -->
<div class="running-header">
  <span>KP Fisheries RFLMS — Citizen User Manual</span>
  <span>Section 2: What Citizens Can Do</span>
</div>

<h2>2. What Citizens &amp; Anglers Can Do (Services &amp; Capabilities)</h2>
<p>
  The public portal offers an array of self-service capabilities designed to make your fishing experience enjoyable, 
  lawful, and transparent.
</p>

<h3>2.1 Public Water Bodies Catalog &amp; Discovery</h3>
<p>
  Available at <code>/water-bodies</code> without requiring login, citizens can explore the provincial fisheries directory:
</p>
<ul>
  <li><strong>Search &amp; Filter:</strong> Search waters by name (e.g. <em>Khanpur</em>, <em>Kundal</em>) and filter by geographic district (e.g. <em>Swat</em>, <em>Haripur</em>, <em>Kohat</em>).</li>
  <li><strong>Ecological &amp; Trout Details:</strong> Discover whether a water body is classified as <em>Trout</em> (cold-water salmonids), <em>Non-trout</em> (warm-water carps/catfish), or <em>Mixed</em>.</li>
  <li><strong>E-Licence Status Check:</strong> Instantly check whether a reservoir is currently <span class="badge badge-success">Open for E-Licence</span> or closed for conservation.</li>
  <li><strong>GPS &amp; Directions:</strong> View exact GPS coordinates (Latitude/Longitude) and open satellite Google Maps pins for trip planning.</li>
  <li><strong>Species Diversity &amp; Notes:</strong> Learn about resident fish species (Brown Trout, Rainbow Trout, Mahseer, Rohu, Mori, Singari) and local gear rules.</li>
</ul>

<h3>2.2 Online Angler Registration &amp; Secure Verification</h3>
<p>
  Citizens can register for a personalized account at <code>/register</code>:
</p>
<ul>
  <li><strong>Email OTP Verification:</strong> Protects against unauthorized accounts by dispatching a secure 6-digit one-time password to your email.</li>
  <li><strong>Single Citizen Account:</strong> Your CNIC-linked account preserves your application history, past permits, payment vouchers, and digital licences in one unified vault.</li>
</ul>

<h3>2.3 Complete Digital Angler Profile Management</h3>
<p>
  Located at <code>/account/profile</code>, anglers maintain their verified civil identity:
</p>
<ul>
  <li><strong>Profile Dossier:</strong> Full Name, Father's Name, CNIC, Mobile, Date of Birth, Gender, Province, Home District, and Postal Address.</li>
  <li><strong>Angler Card Photo Upload:</strong> Direct upload of your portrait photo (JPG, PNG, max 2MB) that is automatically rendered on the face of your digital e-licence card.</li>
  <li><strong>Emergency Contact Information:</strong> Registering emergency contact details for safety while fishing in remote river valleys or deep reservoirs.</li>
</ul>

<h3>2.4 9-Dot Pattern Lock Account Security</h3>
<p>
  For anglers accessing the system on shared family devices or public computers, RFLMS provides an innovative 
  <strong>9-Dot Pattern Lock</strong> security feature:
</p>
<ul>
  <li><strong>Gesture-Based Security:</strong> Draw an interactive gesture connecting at least 4 dots across a 3x3 grid.</li>
  <li><strong>Two-Factor Protection:</strong> Once enabled, logging in requires both your password and your custom drawn pattern sequence, preventing unauthorized access even if your password is compromised.</li>
</ul>

<h3>2.5 Applying for E-Licences (Daily, Weekly, Monthly, Seasonal)</h3>
<p>
  Anglers can submit permit applications directly from any water body page:
</p>
<table class="data-table">
  <thead>
    <tr>
      <th style="width: 20%;">Category Type</th>
      <th style="width: 25%;">Validity Duration</th>
      <th style="width: 55%;">Purpose &amp; Best Suited For</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><strong>Daily Angling</strong></td>
      <td>1 Calendar Day</td>
      <td>Short weekend trips, day tourists, and one-off recreational visits.</td>
    </tr>
    <tr>
      <td><strong>Weekly Angling</strong></td>
      <td>7 Consecutive Days</td>
      <td>Holiday vacationers spending a full week fishing in valleys like Swat or Kaghan.</td>
    </tr>
    <tr>
      <td><strong>Monthly Angling</strong></td>
      <td>30 Consecutive Days</td>
      <td>Extended seasonal stays and frequent local recreational anglers.</td>
    </tr>
    <tr>
      <td><strong>Seasonal Angling</strong></td>
      <td>Until June 30 of current fiscal year</td>
      <td>Annual passionate anglers. Terminates on June 30 under provincial fiscal rules.</td>
    </tr>
  </tbody>
</table>

<h3>2.6 Multiple Flexible Payment Channels</h3>
<p>
  RFLMS accommodates all modern Pakistani banking options:
</p>
<ul>
  <li><strong>1Bill / PSID System:</strong> Instant electronic invoice code reconcilable via 1Bill on mobile banking apps (e.g. Meezan, HBL, Allied, MCB), EasyPaisa, JazzCash, and ATMs.</li>
  <li><strong>Online Bank Transfer:</strong> Direct interbank transfer (IBFT) to the official KP Fisheries account with upload of the transaction screenshot.</li>
  <li><strong>Bank Deposit Slip:</strong> Cash deposit at National Bank of Pakistan (NBP) or Treasury branch with upload of the stamped deposit counterfoil.</li>
</ul>

<h3>2.7 Real-Time Application Tracking &amp; Feedback</h3>
<p>
  From your dashboard (<code>/account/applications</code>), you can observe every stage of your application:
</p>
<ul>
  <li><strong>Status Indicators:</strong> Track states from <span class="badge badge-warning">Submitted</span> &rarr; <span class="badge badge-info">Under Review</span> &rarr; <span class="badge badge-success">Approved</span>.</li>
  <li><strong>Officer Remarks &amp; Corrections:</strong> If your uploaded slip is unclear, the inspecting officer returns the file with specific instructions under <span class="badge badge-warning">Info Required</span>. You can update your submission directly without re-paying or re-starting.</li>
</ul>

<h3>2.8 Interactive 3D Digital Licence Card &amp; QR Verification</h3>
<p>
  Once sanctioned, your digital licence is available at <code>/account/licenses/{{id}}/card</code>:
</p>
<ul>
  <li><strong>3D Flip Animation:</strong> Interactive card viewer that flips smoothly between Front and Back faces with dynamic ambient lighting and holographic sweep effects.</li>
  <li><strong>Front Face:</strong> Features official KP government emblem, your portrait photo, full name, CNIC, Licence Number, Category badge, validity dates, and high-contrast QR code.</li>
  <li><strong>Back Face:</strong> Displays the legal Daily Bag Limit (catch quota), allowed angling gear, statutory terms, and departmental helpline.</li>
  <li><strong>Print &amp; Offline Save:</strong> One-click print button formatted to output a standard pocket-sized licence card on your home printer or PDF.</li>
</ul>

<div class="page-break-before"></div>

<!-- ==================== SECTION 3 ==================== -->
<div class="running-header">
  <span>KP Fisheries RFLMS — Citizen User Manual</span>
  <span>Section 3: How Citizens Can Do It</span>
</div>

<h2>3. How Citizens Can Do It (Step-by-Step Operational Guide)</h2>
<p>
  This section provides clear, illustrated, step-by-step procedures for every service available on the public site.
</p>

<h3>3.1 How to Create an Account with Email OTP Verification</h3>
<div class="step-box">
  <div class="step-number">PROCEDURE 3.1</div>
  <div class="step-title">Citizen Account Registration</div>
  <ol>
    <li>Navigate to the homepage (<code>https://[portal-domain]/</code>) and click the green <strong>Register</strong> button in the top right (or visit <code>/register</code>).</li>
    <li>Complete the initial registration fields:
      <ul>
        <li><strong>Full Name:</strong> Enter your name as printed on your CNIC.</li>
        <li><strong>Email Address:</strong> Enter an active email address you have access to.</li>
        <li><strong>CNIC No.:</strong> Enter your 13-digit CNIC in standard format: <code>12345-1234567-1</code>.</li>
      </ul>
    </li>
    <li>Click the green button: <strong>Send OTP</strong>.</li>
    <li>Check your email inbox (and Spam/Junk folder) for an email from <em>KP Fisheries RFLMS</em> containing a <strong>6-digit verification code</strong> (valid for 10 minutes).</li>
    <li>On the verification screen (<code>/register/verify</code>):
      <ul>
        <li>Enter the <strong>6-digit OTP</strong>.</li>
        <li>Create a secure <strong>Password</strong> (minimum 8 characters with letters and numbers).</li>
        <li>Re-enter password in <strong>Confirm Password</strong>.</li>
      </ul>
    </li>
    <li>Click <strong>Verify &amp; Create Account</strong>. Your account is activated, and you are automatically logged into your dashboard.</li>
  </ol>
</div>

<h3>3.2 How to Log In &amp; Use 9-Dot Pattern Lock</h3>
<div class="step-box">
  <div class="step-number">PROCEDURE 3.2</div>
  <div class="step-title">Logging In &amp; Pattern Verification</div>
  <ol>
    <li>Visit <code>https://[portal-domain]/login</code>.</li>
    <li>Enter your registered <strong>Email</strong> and <strong>Password</strong>. Click <strong>Sign in</strong>.</li>
    <li><strong>Standard Account:</strong> If Pattern Lock is disabled, you are taken immediately to your Citizen Dashboard.</li>
    <li><strong>Pattern Protected Account:</strong> If you previously enabled Pattern Lock, the system routes you to the <strong>Pattern Verification Screen</strong> (<code>/login/pattern-verify</code>).
      <ul>
        <li>Touch and drag your finger or mouse across the 9-dot circular matrix to connect your secret sequence.</li>
        <li>Click <strong>Verify Pattern &amp; Access Dashboard</strong>.</li>
      </ul>
    </li>
  </ol>
</div>

<h3>3.3 How to Complete Your Angler Profile</h3>
<div class="callout callout-warning">
  <strong>Mandatory Prerequisite:</strong>
  Under departmental regulations, you cannot submit an e-licence application until your profile is complete with a photo and CNIC information.
</div>

<div class="step-box">
  <div class="step-number">PROCEDURE 3.3</div>
  <div class="step-title">Completing the Angler Profile Dossier</div>
  <ol>
    <li>Log into your account and click <strong>Profile</strong> in the navigation bar (or visit <code>/account/profile</code>).</li>
    <li><strong>Profile Picture:</strong> Click <em>Choose File</em> under Profile Picture and upload a clear passport-style face photo (max 2MB, JPG/PNG). This photo will appear on your official licence card.</li>
    <li><strong>Demographics:</strong>
      <ul>
        <li>Verify your <strong>Full Name</strong> and <strong>CNIC</strong>.</li>
        <li>Enter your <strong>Father / Husband Name</strong>.</li>
        <li>Enter your <strong>Mobile Number</strong> (e.g. <code>0300-1234567</code>).</li>
        <li>Select your <strong>Date of Birth</strong> from the calendar.</li>
        <li>Select your <strong>Gender</strong> (<em>Male</em>, <em>Female</em>, <em>Other</em>).</li>
        <li>Select your <strong>Residence District</strong> from the dropdown of 36 KP districts.</li>
        <li>Input your <strong>Postal Address</strong> and an <strong>Emergency Contact</strong> number.</li>
      </ul>
    </li>
    <li>Click <strong>Save profile</strong>. A green alert confirms: <em>"Profile updated successfully."</em></li>
    <li>(Optional) Scroll down to <strong>Pattern Lock Security</strong>, toggle the switch <strong>ON</strong>, and draw a 4+ dot pattern sequence to enable two-factor login protection.</li>
  </ol>
</div>

<div class="page-break-before"></div>

<!-- ==================== SECTION 3: CONTINUED ==================== -->
<div class="running-header">
  <span>KP Fisheries RFLMS — Citizen User Manual</span>
  <span>Section 3: How Citizens Can Do It</span>
</div>

<h3>3.4 How to Explore Water Bodies in the Public Catalog</h3>
<div class="step-box">
  <div class="step-number">PROCEDURE 3.4</div>
  <div class="step-title">Browsing Fishing Waters</div>
  <ol>
    <li>From the top menu, click <strong>Water bodies</strong> (<code>/water-bodies</code>).</li>
    <li>Use the search filter bar:
      <ul>
        <li>Select a <strong>District</strong> (e.g. <em>Swat</em>) to view waters in that area.</li>
        <li>Select a <strong>Type</strong> (e.g. <em>Dam / Reservoir</em> or <em>River</em>).</li>
        <li>Type a query in the search bar (e.g. <em>"Kundal"</em> or <em>"Khanpur"</em>).</li>
      </ul>
    </li>
    <li>Browse the reservoir cards. Each card displays the lake name, district, trout classification, and a status badge:
      <br>• <span class="badge badge-success">Open for E-Licence</span>: Eligible for immediate online application.
      <br>• <span class="badge badge-danger">Closed</span>: Reserved, commercial lease, or under conservation.
    </li>
    <li>Click <strong>View details</strong> on any reservoir to inspect fish species notes, supervising fisheries office contacts, and GPS location.</li>
    <li>Click the green <strong>Apply for Licence</strong> button to begin your application for this water body.</li>
  </ol>
</div>

<h3>3.5 How to Apply for an E-Licence Online</h3>
<div class="step-box">
  <div class="step-number">PROCEDURE 3.5</div>
  <div class="step-title">Submitting an E-Licence Application</div>
  <ol>
    <li>From a reservoir's detail page, click <strong>Apply for Licence</strong> (<code>/account/water-bodies/{{id}}/apply</code>).</li>
    <li>Verify the selected <strong>Water Body</strong> and supervising district office displayed at the top.</li>
    <li><strong>Step 1: Choose Licence Category:</strong>
      <br>In the <em>Licence Category</em> dropdown, select your preferred duration (e.g. <em>Daily Angling Permit — 500 PKR</em> or <em>Seasonal Angling — 3,000 PKR</em>). The fee tariff and validity days are displayed automatically.
    </li>
    <li><strong>Step 2: Select Fishing Start Date:</strong>
      <br>Pick the date when you intend to begin fishing. Defaults to today's date.
      <br><em>Caution: If you choose a date that falls during the statutory Closed Breeding Season (e.g. June 1 to July 31), the system will prevent submission.</em>
    </li>
    <li><strong>Step 3: Choose Payment Method:</strong>
      <ul>
        <li><code>Online bank transfer</code>: If you plan to transfer fee via mobile banking app.</li>
        <li><code>Bank deposit</code>: If depositing physical cash at National Bank / Treasury.</li>
        <li><code>1Bill / PSID</code>: For payment via 1Bill / ATM consumer number.</li>
      </ul>
    </li>
    <li><strong>Step 4: Enter Payment Reference &amp; Attach Proof:</strong>
      <ul>
        <li>For Bank Transfer / Deposit: Enter transaction scroll / TID reference number in <strong>Payment Reference</strong>.</li>
        <li>Click <em>Choose File</em> under <strong>Attach Payment Slip</strong> and upload screenshot or photo of the deposit slip (JPG/PNG/PDF, max 4MB).</li>
      </ul>
    </li>
    <li><strong>Step 5: Consent &amp; Submit:</strong>
      <br>Check the box: <strong>"I agree to the terms and regulations of Khyber Pakhtunkhwa Fisheries Department."</strong>
      <br>Click the green button: <strong>Submit Application</strong>.
    </li>
    <li>The system displays the <strong>Application Success Screen</strong> (<code>/account/applications/{{id}}/success</code>) showing your unique <strong>Application Tracking Number</strong> (e.g. <code>APP-2026-00045</code>).</li>
  </ol>
</div>

<h3>3.6 How to Pay via 1Bill, Bank Transfer, or Deposit Slip</h3>
<table class="data-table">
  <thead>
    <tr>
      <th style="width: 25%;">Payment Channel</th>
      <th style="width: 75%;">Step-by-Step Instructions</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><strong>1Bill / PSID</strong><br><span class="badge badge-primary">Fastest</span></td>
      <td>
        1. On the success screen, note your <strong>PSID Code</strong> (e.g. <code>PSID-DEMO-XXXXXXXXXX</code>).<br>
        2. Open your mobile banking app (HBL, Meezan, Bank of Khyber, EasyPaisa, etc.).<br>
        3. Navigate to <em>Bill Payments</em> &rarr; select <strong>1Bill / Government Payments</strong>.<br>
        4. Enter your PSID number. The system retrieves your exact fee and applicant name.<br>
        5. Confirm payment. Your application transitions automatically for staff verification.
      </td>
    </tr>
    <tr>
      <td><strong>Online Bank Transfer (IBFT)</strong></td>
      <td>
        1. Transfer the fee amount to the official departmental account displayed on screen.<br>
        2. Take a clear screenshot of the bank transfer confirmation showing Transaction ID (TID), Date, and Amount.<br>
        3. Upload the image during application submission and enter the TID in the Payment Reference field.
      </td>
    </tr>
    <tr>
      <td><strong>Bank Deposit Slip (NBP)</strong></td>
      <td>
        1. Visit any branch of National Bank of Pakistan (NBP) or designated Treasury Office.<br>
        2. Fill the departmental credit voucher under Head of Account: <em>Fisheries Receipts</em>.<br>
        3. Snap a clear photo of the stamped bank deposit counterfoil and upload it with your application.
      </td>
    </tr>
  </tbody>
</table>

<div class="page-break-before"></div>

<!-- ==================== SECTION 3: CONTINUED ==================== -->
<div class="running-header">
  <span>KP Fisheries RFLMS — Citizen User Manual</span>
  <span>Section 3: How Citizens Can Do It</span>
</div>

<h3>3.7 How to Track Your Application Status &amp; Respond to Info Requests</h3>
<div class="step-box">
  <div class="step-number">PROCEDURE 3.7</div>
  <div class="step-title">Tracking Progress &amp; Responding to Queries</div>
  <ol>
    <li>Log in and click <strong>Applications</strong> in the top navigation (<code>/account/applications</code>).</li>
    <li>Locate your application in the list to check the current status badge:
      <ul>
        <li><span class="badge badge-info">Submitted / Under Review</span>: File is currently being audited by the district fisheries officer.</li>
        <li><span class="badge badge-success">Approved</span>: Permit is active! Click <em>View Licence Card</em> to open your permit.</li>
        <li><span class="badge badge-warning">Info Required</span>: The inspecting officer has requested clarification.</li>
        <li><span class="badge badge-danger">Rejected</span>: Application denied with official explanation.</li>
      </ul>
    </li>
    <li><strong>If Status is "Info Required":</strong>
      <ul>
        <li>Click <strong>View details</strong> (<code>/account/applications/{{id}}</code>).</li>
        <li>Read the <strong>Officer Remarks</strong> (e.g. <em>"Payment receipt is illegible. Please upload a clear scan of the deposit slip."</em>).</li>
        <li>Click the <strong>Update Application</strong> button, upload the requested document, and re-submit. The officer is notified immediately.</li>
      </ul>
    </li>
  </ol>
</div>

<h3>3.8 How to View, Flip &amp; Print Your Digital Licence Card</h3>
<div class="step-box">
  <div class="step-number">PROCEDURE 3.8</div>
  <div class="step-title">Accessing &amp; Printing the 3D E-Licence Card</div>
  <ol>
    <li>From your Applications page, locate an approved permit and click <strong>View Licence Card</strong> (<code>/account/licenses/{{id}}/card</code>).</li>
    <li>The interactive 3D Card Scene loads:
      <ul>
        <li><strong>Flip the Card:</strong> Click anywhere on the card (or tap the <em>"Flip Card"</em> button) to toggle between Front and Back faces with dynamic 3D rotation.</li>
        <li><strong>Front Face Review:</strong> Check your Photo, Full Name, CNIC, Licence Number (e.g. <code>RFL-2026-00045</code>), Water Body, and Validity Period.</li>
        <li><strong>Back Face Review:</strong> Review the Daily Bag Limit (e.g. <em>6 Fish per Day</em>), authorized angling gear, and safety guidelines.</li>
      </ul>
    </li>
    <li><strong>Printing Your Card:</strong>
      <ul>
        <li>Click the blue <strong>Print Licence Card</strong> button in the top toolbar.</li>
        <li>The browser print dialog opens automatically formatted for pocket card printing or standard A4 paper.</li>
        <li>Save as PDF to keep an offline copy on your smartphone when fishing in areas without cell reception.</li>
      </ul>
    </li>
  </ol>
</div>

<h3>3.9 How to Report Illegal Fishing / Poaching (Whistleblowing)</h3>
<p>
  Citizens are the first line of defense in protecting KP's rivers and lakes. Anyone can submit a geo-tagged violation report:
</p>

<div class="step-box">
  <div class="step-number">PROCEDURE 3.9</div>
  <div class="step-title">Submitting an Illegal Fishing Incident Report</div>
  <ol>
    <li>Navigate to <strong>Report illegal fishing</strong> in the top menu (or visit <code>https://[portal-domain]/report-violation</code>). No login is required.</li>
    <li>Select the <strong>District</strong> where the incident occurred.</li>
    <li>(Optional) Select the specific <strong>Water Body</strong> from the filtered list.</li>
    <li>Select the <strong>Violation Type</strong>:
      <br>• <em>Dynamite / Explosives</em> · <em>Electric Generator Shock</em> · <em>Poisoning / Chemicals</em> · <em>Fine-Mesh Netting</em> · <em>Closed Season Fishing</em> · <em>Unauthorized Fishing</em>.
    </li>
    <li>Select <strong>When Occurred</strong> using the date-time picker.</li>
    <li>In <strong>Description</strong>, explain the incident: number of poachers, boat descriptions, vehicle numbers, or landmark references.</li>
    <li><strong>Location Coordinates:</strong> Enter Latitude and Longitude if known, or click the GPS icon to auto-capture your phone's coordinates.</li>
    <li><strong>Evidence Photos:</strong> Click <em>Choose Files</em> to attach up to 3 photographs of the violation (nets, gear, vehicle).</li>
    <li><strong>Reporter Identity:</strong> If logged out, you may enter your name and phone number, or leave blank to report <strong>100% Anonymously</strong>.</li>
    <li>Click the green button: <strong>Submit report</strong>. The incident is immediately dispatched to the local DFO for investigation.</li>
  </ol>
</div>

<h3>3.10 How to Verify Any E-Licence via Public QR Scan</h3>
<div class="step-box">
  <div class="step-number">PROCEDURE 3.10</div>
  <div class="step-title">Public Licence QR Verification</div>
  <ol>
    <li>Open your smartphone camera app or any QR reader.</li>
    <li>Point your camera at the QR code printed on the angler's digital screen or paper card.</li>
    <li>Tap the verification link: <code>https://[portal-domain]/verify/licence/[qr_token]</code>.</li>
    <li>Verify the verification screen:
      <ul>
        <li><span class="badge badge-success">Authentic E-Licence Verified</span>: Confirms genuine active permit issued by KP Fisheries.</li>
        <li><strong>Privacy Shield:</strong> Holder name is partially masked (e.g. <code>M*******d A*i K**n</code>) to safeguard citizen privacy while verifying authenticity.</li>
        <li>Check water body authorization and ensure current date falls within valid dates.</li>
      </ul>
    </li>
  </ol>
</div>

<div class="page-break-before"></div>

<!-- ==================== SECTION 4 ==================== -->
<div class="running-header">
  <span>KP Fisheries RFLMS — Citizen User Manual</span>
  <span>Section 4: Legal Angling Rules</span>
</div>

<h2>4. Legal Angling Rules, Bag Limits &amp; Conservation</h2>
<p>
  Anglers operating in Khyber Pakhtunkhwa are legally bound by the provisions of the <strong>Khyber Pakhtunkhwa Fisheries Act</strong> 
  and the regulations printed on their licence cards.
</p>

<h3>4.1 Statutory Closed Breeding Seasons (Spawning Protection)</h3>
<ul>
  <li><strong>Conservation Mandate:</strong> During natural breeding periods, fish congregate in shallow spawning beds and are highly vulnerable. Fishing during these windows is strictly prohibited by law.</li>
  <li><strong>Annual Summer Spawning Closure:</strong> Across provincial reservoirs and warm-water rivers, fishing is closed annually between <strong>June 1 and July 31</strong> (or dates officially notified in the Government Gazette).</li>
  <li><strong>Automated Block:</strong> The e-licensing system automatically rejects any application with a start date during the closed season. Attempting to fish with an expired or fraudulent permit during this period carries double statutory penalties.</li>
</ul>

<h3>4.2 Daily Bag Limits (Catch Quotas) by Fishery Zone</h3>
<p>
  To prevent over-exploitation and preserve trophy sportfishing for all anglers, strict daily retention limits apply:
</p>
<table class="data-table">
  <thead>
    <tr>
      <th style="width: 25%;">Fishery Zone</th>
      <th style="width: 30%;">Target Species</th>
      <th style="width: 45%;">Daily Bag Limit &amp; Gear Restrictions</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><strong>Trout Waters</strong><br>(Swat, Kumrat, Kaghan)</td>
      <td>Brown Trout, Rainbow Trout</td>
      <td><strong>Maximum 6 fish per day per angler.</strong><br>Gear strictly limited to single-hook artificial fly or spinning lure. Live bait, worms, and multiple treble hooks are strictly prohibited.</td>
    </tr>
    <tr>
      <td><strong>Mahseer Sanctuaries</strong><br>(Poonch, Siran, Tanda)</td>
      <td>Golden Mahseer</td>
      <td><strong>Catch-and-Release strongly encouraged.</strong> Maximum retention of 2 fish per day. Barbless single hooks recommended.</td>
    </tr>
    <tr>
      <td><strong>Reservoirs &amp; Dams</strong><br>(Tarbela, Khanpur)</td>
      <td>Rohu, Mori, Thaila, Catfish</td>
      <td><strong>Maximum 10 kg total catch per day.</strong><br>Hook-and-line angling only (maximum 2 rods per licensed angler). Cast nets and gill nets strictly forbidden.</td>
    </tr>
  </tbody>
</table>

<h3>4.3 Prohibited Fishing Methods (Criminal Offenses)</h3>
<div class="callout callout-danger">
  <strong>Zero Tolerance Legal Notice:</strong>
  Under Section 12 of the Khyber Pakhtunkhwa Fisheries Act, the following destructive fishing methods are classified as 
  <strong>non-bailable criminal offenses</strong> punishable by heavy fines, confiscation of vehicles, and imprisonment:
  <ul style="margin-top: 4pt;">
    <li><strong>Dynamite &amp; Explosives:</strong> Detonating explosive charges in rivers, pools, or reservoirs.</li>
    <li><strong>Electric Shock Generation:</strong> Using generators, batteries, or inverters to shock water bodies.</li>
    <li><strong>Poisoning &amp; Toxic Chemicals:</strong> Dumping bleaching powder, pesticides, or toxic herbs into streams.</li>
    <li><strong>Fine-Mesh / Monofilament Gill Nets:</strong> Using mosquito nets or monofilament gill netting that indiscriminately traps juvenile fingerlings.</li>
  </ul>
</div>

<h3>4.4 Single Active Licence Policy per Water Body</h3>
<p>
  The RFLMS database enforces a <strong>single active licence constraint</strong>. An angler cannot hold two overlapping active licences 
  for the same water body simultaneously. If you wish to fish in a different reservoir (e.g. you hold a Khanpur Dam permit and wish to fish 
  in Swat River), you must apply for a separate permit designated for Swat River.
</p>

<div class="page-break-before"></div>

<!-- ==================== SECTION 5 ==================== -->
<div class="running-header">
  <span>KP Fisheries RFLMS — Citizen User Manual</span>
  <span>Section 5: Troubleshooting &amp; FAQ</span>
</div>

<h2>5. Citizen Troubleshooting &amp; Frequently Asked Questions (FAQ)</h2>

<dl class="key-value-list">
  <dt>Q1: I did not receive my 6-digit registration OTP email. What should I do?</dt>
  <dd>
    <strong>Resolution:</strong>
    <br>1. Check your email's <strong>Spam</strong>, <strong>Junk</strong>, or <strong>Promotions</strong> folder.
    <br>2. Ensure you entered your email address correctly without typos.
    <br>3. On the registration screen, wait for the 60-second cooldown timer to elapse and click <strong>Resend OTP</strong>.
  </dd>

  <dt>Q2: When I try to apply for a licence, the system says "Please complete your profile before applying". Why?</dt>
  <dd>
    <strong>Resolution:</strong> You must navigate to <code>/account/profile</code> and complete all mandatory fields: Father's Name, Date of Birth, Gender, Home District, Postal Address, Emergency Contact, and upload a clear portrait photo. Once saved, you can immediately apply.
  </dd>

  <dt>Q3: Why is my preferred fishing date blocked on the calendar?</dt>
  <dd>
    <strong>Resolution:</strong> The date you selected falls inside the Government's statutory Closed Breeding Season (e.g. June 1 to July 31). Fishing is legally banned during spawning to allow fish to reproduce. Please choose a date before or after the closed season.
  </dd>

  <dt>Q4: How long does it take for an application to be approved?</dt>
  <dd>
    <strong>Resolution:</strong> Applications submitted with verified 1Bill / PSID payments are typically reviewed and approved within <strong>2 to 6 business hours</strong>. Bank deposit slip reviews may take up to 24 hours while treasury accounts are reconciled.
  </dd>

  <dt>Q5: What should I do if my application status says "Info Required"?</dt>
  <dd>
    <strong>Resolution:</strong> Click on your application to view the inspecting officer's comments. Common requests include re-uploading a clearer photo of your bank deposit slip or updating your CNIC details. Update the requested information and re-submit. You do not have to pay again.
  </dd>

  <dt>Q6: Can I fish in Swat River with a Khanpur Dam permit?</dt>
  <dd>
    <strong>Resolution:</strong> No. Every recreational angling licence is strictly water-body specific. Swat River is a cold-water trout fishery governed by different bag limits and tariffs than Khanpur Dam. You must hold a valid permit for each specific water body you fish in.
  </dd>

  <dt>Q7: Do I need to print my licence card, or is the digital card on my phone sufficient?</dt>
  <dd>
    <strong>Resolution:</strong> The digital card on your smartphone screen is 100% legally recognized. When approached by fisheries wardens, present your phone with the card open so they can scan the QR code. However, printing a physical card is highly recommended if visiting remote valleys where mobile battery or network coverage may be limited.
  </dd>
</dl>

<div class="callout callout-success" style="margin-top: 12pt;">
  <strong>Departmental Citizen Support &amp; Angling Inquiries:</strong><br>
  For assistance with licensing, portal issues, or angling guidelines, contact our citizen helpdesk:<br>
  <strong>Email:</strong> <code>info.fisheries@kp.gov.pk</code> | <strong>Helpdesk Hotline:</strong> 091-9210000<br>
  <strong>Official Website:</strong> <code>https://fisheries.kp.gov.pk</code><br>
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
    print("KP Fisheries RFLMS Public & Citizen User Manual Dual-Pass Builder")
    print("-" * 65)
    
    markers = {
        "sec1": "1. Introduction & Public Services Overview",
        "sec1_1": "1.1 Welcome to KP Fisheries E-Licensing",
        "sec1_2": "1.2 Why Digital E-Licensing?",
        "sec1_3": "1.3 Public Access Points & Browser Compatibility",
        "sec1_4": "1.4 Prerequisites for Applying",
        "sec2": "2. What Citizens & Anglers Can Do (Services & Capabilities)",
        "sec2_1": "2.1 Public Water Bodies Catalog & Discovery",
        "sec2_2": "2.2 Online Angler Registration & Secure Verification",
        "sec2_3": "2.3 Complete Digital Angler Profile Management",
        "sec2_4": "2.4 9-Dot Pattern Lock Account Security",
        "sec2_5": "2.5 Applying for E-Licences (Daily, Weekly, Monthly, Seasonal)",
        "sec2_6": "2.6 Multiple Flexible Payment Channels",
        "sec2_7": "2.7 Real-Time Application Tracking & Feedback",
        "sec2_8": "2.8 Interactive 3D Digital Licence Card & QR Verification",
        "sec3": "3. How Citizens Can Do It (Step-by-Step Operational Guide)",
        "sec3_1": "3.1 How to Create an Account with Email OTP Verification",
        "sec3_2": "3.2 How to Log In & Use 9-Dot Pattern Lock",
        "sec3_3": "3.3 How to Complete Your Angler Profile",
        "sec3_4": "3.4 How to Explore Water Bodies in the Public Catalog",
        "sec3_5": "3.5 How to Apply for an E-Licence Online",
        "sec3_6": "3.6 How to Pay via 1Bill, Bank Transfer, or Deposit Slip",
        "sec3_7": "3.7 How to Track Your Application Status & Respond to Info Requests",
        "sec3_8": "3.8 How to View, Flip & Print Your Digital Licence Card",
        "sec3_9": "3.9 How to Report Illegal Fishing / Poaching",
        "sec3_10": "3.10 How to Verify Any E-Licence via Public QR Scan",
        "sec4": "4. Legal Angling Rules, Bag Limits & Conservation",
        "sec4_1": "4.1 Statutory Closed Breeding Seasons (Spawning Protection)",
        "sec4_2": "4.2 Daily Bag Limits (Catch Quotas) by Fishery Zone",
        "sec4_3": "4.3 Prohibited Fishing Methods (Criminal Offenses)",
        "sec4_4": "4.4 Single Active Licence Policy per Water Body",
        "sec5": "5. Citizen Troubleshooting & Frequently Asked Questions (FAQ)"
    }
    
    html_path = "/var/www/kp-fisheries-e-license/docs/KP_Fisheries_RFLMS_Public_User_Manual.html"
    pdf_path = "/var/www/kp-fisheries-e-license/docs/KP_Fisheries_RFLMS_Public_User_Manual.pdf"
    public_pdf_path = "/var/www/kp-fisheries-e-license/public/docs/KP_Fisheries_RFLMS_Public_User_Manual.pdf"
    
    # PASS 1: Build draft HTML & render draft PDF
    print("[*] PASS 1: Rendering initial draft...")
    draft_html = build_public_manual_html()
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
    final_html = build_public_manual_html(detected_pages)
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
    
    print("-" * 65)
    print(f"[SUCCESS] Final Public Manual Generated: {pdf_path}")
    print(f"[SUCCESS] Total Pages: {total_pages}")
    print(f"[SUCCESS] File Size: {pdf_size} bytes ({pdf_size / (1024*1024):.2f} MB)")
    print(f"[SUCCESS] Public Download URL: /docs/KP_Fisheries_RFLMS_Public_User_Manual.pdf")

if __name__ == "__main__":
    main()
