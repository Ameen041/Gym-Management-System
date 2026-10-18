{{-- Terms & conditions (English). Numbers come from the code: $planDays, $chatDays, $minAge --}}
<p class="legal-lead">Himma is an online platform that connects trainees with independent fitness coaches who give workout and nutrition plans and follow-up remotely. By using Himma or creating an account you accept these terms and the <a href="{{ route('privacy') }}">privacy policy</a>.</p>

<nav class="legal-toc" aria-label="Contents">
  <a href="#account">Account</a>
  <a href="#coaches-role">Himma and the coach</a>
  <a href="#payment">Subscriptions and payment</a>
  <a href="#refunds">Refunds</a>
  <a href="#chat">Talking on Himma</a>
  <a href="#health">Health notice</a>
  <a href="#coaches">Terms for coaches</a>
  <a href="#content">Content and reviews</a>
  <a href="#ending">Suspension and liability</a>
</nav>

<h2 id="account">1. Account</h2>
<ul>
  <li>You must be {{ $minAge }} or older. If you are under 18, subscribing means your parent or guardian agrees to it.</li>
  <li>Give true information, use one account that is yours only, and keep your password secret. You are responsible for what happens from your account.</li>
</ul>

<h2 id="coaches-role">2. Himma and the coach</h2>
<ul>
  <li>Coaches are independent, not Himma employees. Each coach is responsible for their plans and follow-up.</li>
  <li>Himma reviews each coach's profile and certificates before trainees can see it, but cannot promise a result: results depend on your commitment and your health.</li>
</ul>

<h2 id="payment">3. Subscriptions and payment</h2>
<ul>
  <li>You pay with Sham Cash to the Himma account shown on the payment page only, then upload the receipt with the transaction number.</li>
  <li>The subscription starts once the admin confirms the money arrived; the coach sends your plan within {{ $planDays }} days of the confirmation.</li>
  <li>Himma keeps a commission on each subscription; coaches see their net earnings in their dashboard.</li>
  <li><strong>Paying a coach directly or outside Himma in any way is not allowed.</strong> Himma cannot guarantee or refund money paid outside the platform.</li>
  <li>A renewal starts right after the current subscription ends.</li>
</ul>

<h2 id="refunds">4. Refunds</h2>
<ul>
  <li>If the coach does not send your plan within {{ $planDays }} days of the payment confirmation, you can get all your money back or move to another coach.</li>
  <li>If a payment is rejected, or you cancel before it is confirmed, nothing is charged.</li>
  <li>Once you have your plan there is no refund, unless the coach clearly fails to give the service (for example stops following up). The admin reviews the case and decides; a refund can be partial, based on the time left.</li>
  <li>Money goes back to the Sham Cash account you paid from. To ask for a refund, use "Report" in the chat or the contact form.</li>
</ul>

<h2 id="chat">5. Talking on Himma</h2>
<ul>
  <li>Coaches and trainees talk in the Himma chat only, and never see each other's phone number.</li>
  <li><strong>Sharing phone numbers, accounts, links or usernames on other apps, or agreeing to pay outside Himma, is not allowed.</strong> Messages that contain them are not sent, and Himma keeps them.</li>
  <li>A first breach gets a warning; repeating it can suspend the account and stop the coach's earnings linked to it.</li>
  <li>Chats are private: the Himma team reads one only if one of you reports it, or to settle a dispute.</li>
  <li>The chat stays open during the subscription and {{ $chatDays }} days after it ends.</li>
  <li>Abuse, harassment and inappropriate content are not allowed.</li>
</ul>

<h2 id="health">6. Health notice</h2>
<div class="legal-box">
  <ul>
    <li>Plans and advice on Himma are general fitness and nutrition guidance from your coach, <strong>not a medical diagnosis, treatment, or a replacement for a doctor or a dietitian.</strong></li>
    <li>Before subscribing you answer health questions. If you answer "yes" to any, have a long-term condition, are pregnant or take medicine, check with your doctor before you start and tell your coach what they said.</li>
    <li><strong>Stop training at once and get medical help</strong> if you feel chest pain, severe shortness of breath, dizziness, fainting or unusual pain.</li>
    <li>You are responsible for true answers, and for telling your coach about any change in your health.</li>
    <li>Diets for medical conditions (such as diabetes or kidney disease) need a doctor or a dietitian.</li>
    <li>Coaches on Himma do not prescribe medicine, treat diseases or recommend steroids.</li>
  </ul>
</div>

<h2 id="coaches">7. Terms for coaches</h2>
<ul>
  <li>Give true information and certificates, send plans on time, and answer check-ins and messages.</li>
  <li>Respect the client's health answers, send them to a doctor when needed, and never give a medical diagnosis, medicine or steroids.</li>
  <li>Never ask for or accept payment outside Himma, and never share outside contact details.</li>
  <li>Each subscription's earnings are ready once the plan is sent and the hold period has passed, and are paid to the Sham Cash account the coach added.</li>
  <li>The admin can hide a coach's profile or suspend the account if these terms are broken.</li>
</ul>

<h2 id="content">8. Content and reviews</h2>
<ul>
  <li>Reviews must be honest and about your own experience. The admin removes abusive or misleading reviews.</li>
  <li>Your photos and check-ins are private and never published.</li>
  <li>Platform content (exercises, videos, design) belongs to Himma and may not be copied for commercial use.</li>
</ul>

<h2 id="ending">9. Suspension and liability</h2>
<ul>
  <li>Himma can suspend any account that breaks these terms, misuses the platform or gets around the payment.</li>
  <li>You can stop using Himma at any time and ask to delete your account, as described in the privacy policy.</li>
  <li>Himma is not responsible for injuries or harm caused by ignoring the health notice or hiding health information, or for any agreement or payment made outside the platform.</li>
  <li>The platform may stop for a while for maintenance, or because of internet or power cuts.</li>
  <li>We may change these terms; important changes are announced in the app, and using Himma afterwards means you accept them.</li>
  <li>These terms follow the laws in force in the Syrian Arab Republic. Contact: the <a href="{{ route('home') }}#contact">contact form</a>.</li>
</ul>
