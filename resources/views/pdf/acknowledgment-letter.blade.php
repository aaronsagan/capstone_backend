<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Donation Acknowledgment Letter</title>
    <style>
        @page {
            margin: 0;
        }
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 40px 60px;
            color: #333;
            line-height: 1.6;
        }
        .letterhead {
            text-align: center;
            border-bottom: 3px solid #667eea;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .letterhead h1 {
            color: #667eea;
            margin: 0;
            font-size: 28px;
        }
        .letterhead p {
            margin: 5px 0;
            font-size: 12px;
            color: #666;
        }
        .date-section {
            text-align: right;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .recipient {
            margin-bottom: 30px;
        }
        .content {
            margin-bottom: 30px;
            text-align: justify;
        }
        .content p {
            margin-bottom: 15px;
        }
        .donation-details {
            background: #f8f9fa;
            padding: 20px;
            border-left: 4px solid #667eea;
            margin: 30px 0;
        }
        .donation-details h3 {
            margin-top: 0;
            color: #667eea;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: bold;
            color: #555;
        }
        .detail-value {
            color: #333;
        }
        .signature-section {
            margin-top: 50px;
        }
        .signature {
            margin-top: 60px;
        }
        .signature-line {
            border-top: 2px solid #333;
            width: 250px;
            margin-top: 10px;
        }
        .signature p {
            margin: 5px 0;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #667eea;
            font-size: 11px;
            color: #666;
            text-align: center;
        }
        .highlight-amount {
            font-size: 18px;
            font-weight: bold;
            color: #667eea;
        }
        .tax-note {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <!-- Letterhead -->
    <div class="letterhead">
        <h1>CharityHub Philippines</h1>
        <p>A Platform for Transparent Giving</p>
        <p>Email: support@charityhub.ph | Website: www.charityhub.ph</p>
    </div>

    <!-- Date -->
    <div class="date-section">
        <strong>Date:</strong> {{ \Carbon\Carbon::parse($donationDate)->format('F d, Y') }}
    </div>

    <!-- Recipient -->
    <div class="recipient">
        <p>Dear {{ $donorName }},</p>
    </div>

    <!-- Content -->
    <div class="content">
        <p>
            On behalf of <strong>{{ $charityName }}</strong>, we extend our heartfelt gratitude for your generous donation. 
            Your commitment to supporting our mission is truly inspiring and makes a significant difference in the lives we serve.
        </p>
    </div>

    <!-- Donation Details -->
    <div class="donation-details">
        <h3>Donation Details</h3>
        <div class="detail-row">
            <span class="detail-label">Donor Name:</span>
            <span class="detail-value">{{ $donorName }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Donation Amount:</span>
            <span class="detail-value highlight-amount">₱{{ number_format($amount, 2) }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Campaign/Purpose:</span>
            <span class="detail-value">{{ $campaignTitle }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Charity:</span>
            <span class="detail-value">{{ $charityName }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Transaction ID:</span>
            <span class="detail-value">{{ $transactionId }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Receipt Number:</span>
            <span class="detail-value">{{ $receiptNumber }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Date of Donation:</span>
            <span class="detail-value">{{ \Carbon\Carbon::parse($donationDate)->format('F d, Y h:i A') }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Verification Date:</span>
            <span class="detail-value">{{ \Carbon\Carbon::parse($verifiedDate)->format('F d, Y h:i A') }}</span>
        </div>
    </div>

    <!-- Tax Information -->
    <div class="tax-note">
        <strong>📋 Tax Information:</strong> This letter serves as official acknowledgment of your donation. 
        Please retain this document for your tax records. {{ $charityName }} is a registered charitable organization, 
        and your donation may be tax-deductible as per Philippine tax laws.
    </div>

    <!-- Impact Message -->
    <div class="content">
        <p>
            Your contribution will be used {{ $campaignTitle !== 'General Support' ? 'for ' . $campaignTitle : 'to support our general operations and programs' }}. 
            We are committed to transparency and will keep you updated on how your donation creates positive change in our community.
        </p>

        <p>
            Once again, thank you for your generosity. Your support enables us to continue our work and expand our reach to those who need it most. 
            Together, we are making a lasting impact.
        </p>
    </div>

    <!-- Signature Section -->
    <div class="signature-section">
        <p>With sincere gratitude,</p>
        
        <div class="signature">
            <div class="signature-line"></div>
            <p><strong>{{ $charityRepresentative }}</strong></p>
            <p>{{ $charityRole }}</p>
            <p>{{ $charityName }}</p>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>
            <strong>CharityHub Philippines</strong> | Empowering Transparent Charitable Giving<br>
            This is an automatically generated acknowledgment letter. For questions, please contact the charity directly 
            or visit your donor dashboard at www.charityhub.ph
        </p>
        <p style="margin-top: 10px;">
            Document ID: ACK-{{ $transactionId }}-{{ date('Ymd') }}
        </p>
    </div>
</body>
</html>
