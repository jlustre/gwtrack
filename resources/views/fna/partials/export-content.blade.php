<h2>Client Information</h2>
<table class="info">
    <tr><td class="label">Client Name</td><td>{{ $client['name'] }}</td><td class="label">Email</td><td>{{ $client['email'] }}</td></tr>
    <tr><td class="label">Phone</td><td>{{ $client['phone'] }}</td><td class="label">Date of Birth</td><td>{{ $client['date_of_birth'] }}</td></tr>
    <tr><td class="label">Occupation</td><td>{{ $client['occupation'] }}</td><td class="label">Location</td><td>{{ $client['location'] }}</td></tr>
</table>

<h2>Household</h2>
@if ($household['restricted'] ?? false)
    <div class="restricted">Financial household details are restricted for this viewer.</div>
@else
    <table class="info">
        <tr><td class="label">Spouse / Partner</td><td>{{ $household['spouse_partner_name'] }}</td><td class="label">Children</td><td>{{ $household['children_count'] }}</td></tr>
        <tr><td class="label">Household Income</td><td>{{ $household['household_income'] }}</td><td class="label">Household Expenses</td><td>{{ $household['household_expenses'] }}</td></tr>
    </table>
@endif

<h2>Income</h2>
@if ($income['restricted'] ?? false)
    <div class="restricted">Income details are restricted for this viewer.</div>
@else
    <table class="info">
        <tr><td class="label">Annual Income</td><td>{{ $income['annual_income'] }}</td><td class="label">Monthly Income</td><td>{{ $income['monthly_income'] }}</td></tr>
        <tr><td class="label">Spouse Annual</td><td>{{ $income['spouse_annual_income'] }}</td><td class="label">Business / Passive</td><td>{{ $income['business_income'] }} / {{ $income['passive_income'] }}</td></tr>
    </table>
@endif

<h2>Debt &amp; Assets</h2>
@if (($debt['restricted'] ?? false) || ($assets['restricted'] ?? false))
    <div class="restricted">Debt and asset details are restricted for this viewer.</div>
@else
    <table class="info">
        <tr><td class="label">Total Debt</td><td>{{ $debt['total_debt'] }}</td><td class="label">Total Assets</td><td>{{ $assets['total_assets'] }}</td></tr>
        <tr><td class="label">Mortgage</td><td>{{ $debt['mortgage_balance'] }}</td><td class="label">Checking / Savings</td><td>{{ $assets['checking_savings'] }}</td></tr>
    </table>
@endif

<h2>Existing Coverage</h2>
@if ($coverage['restricted'] ?? false)
    <div class="restricted">Coverage details are restricted for this viewer.</div>
@else
    <table class="info">
        <tr><td class="label">Life Insurance</td><td>{{ $coverage['existing_life_insurance_amount'] }}</td><td class="label">Term Coverage</td><td>{{ $coverage['term_coverage'] }}</td></tr>
    </table>
@endif

<h2>Goals &amp; Risk</h2>
<table class="info">
    <tr><td class="label">Selected Goals</td><td colspan="3">{{ implode(', ', $goals['selected_goals']) }}</td></tr>
    <tr><td class="label">Main Concern</td><td>{{ $risk['main_financial_concern'] }}</td><td class="label">Urgency / Tolerance</td><td>{{ $risk['urgency_level'] }} / {{ $risk['risk_tolerance'] }}</td></tr>
</table>

<h2>DIME Analysis</h2>
@if ($dime['restricted'] ?? false)
    <div class="restricted">DIME results are restricted for this viewer.</div>
@elseif (! ($dime['completed'] ?? false))
    <div class="restricted">DIME analysis not completed.</div>
@else
    <table class="info">
        <tr><td class="label">Total DIME Need</td><td>{{ $dime['total_dime_need'] }}</td><td class="label">Protection Gap</td><td>{{ $dime['estimated_protection_gap'] }}</td></tr>
        <tr><td class="label">Debt (D)</td><td>{{ $dime['total_debt'] }}</td><td class="label">Income (I)</td><td>{{ $dime['total_income_need'] }}</td></tr>
        <tr><td class="label">Mortgage (M)</td><td>{{ $dime['total_mortgage_need'] }}</td><td class="label">Education (E)</td><td>{{ $dime['total_education_need'] }}</td></tr>
        <tr><td class="label">Recommended Range</td><td colspan="3">{{ $dime['recommended_coverage_min'] }} – {{ $dime['recommended_coverage_max'] }}</td></tr>
    </table>
@endif

<h2>Summary &amp; Recommendations</h2>
<table class="info">
    <tr><td class="label">Main Needs</td><td colspan="3">{{ $summary['main_needs_identified'] }}</td></tr>
    <tr><td class="label">Next Action</td><td colspan="3">{{ $summary['recommended_next_action'] }}</td></tr>
    <tr><td class="label">Associate Recommendation</td><td colspan="3">{{ $summary['associate_recommendation'] }}</td></tr>
    <tr><td class="label">Protection Gap</td><td>{{ $summary['protection_gap'] }}</td><td class="label">Submitted / Approved</td><td>{{ $summary['submitted_at'] ?? '—' }} / {{ $summary['approved_at'] ?? '—' }}</td></tr>
</table>

@if ($cfm_feedback)
    <h2>CFM Feedback</h2>
    <div class="comment">{{ $cfm_feedback }}</div>
@endif

@if (count($review_comments))
    <h2>Review Comments</h2>
    @foreach ($review_comments as $comment)
        <div class="comment">
            <strong>{{ $comment['author'] }}</strong> · {{ $comment['created_at']?->format('M j, Y g:i A') }}<br>
            {{ $comment['body'] }}
        </div>
    @endforeach
@endif

@if (count($status_history))
    <h2>Status History</h2>
    <ul>
        @foreach ($status_history as $history)
            <li>{{ $history['from'] }} → {{ $history['to'] }} · {{ $history['changed_by'] }} · {{ $history['created_at']?->format('M j, Y g:i A') }}</li>
        @endforeach
    </ul>
@endif
