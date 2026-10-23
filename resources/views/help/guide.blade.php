{{-- Agency user guide. Plain HTML only, so the same markup works on the web page and in the PDF. --}}
<h2 id="overview">1. How it all fits together</h2>
<p><b>{{ config('board.brand') }}</b> gives your agency one shared place to plan, approve and track client content. Everything is organised in four levels:</p>
<table class="g">
  <tr><th style="width:26%">Level</th><th>What it is</th></tr>
  <tr><td><b>Agency</b></td><td>Your company. It has an owner, optional team members, a brand colour and document templates.</td></tr>
  <tr><td><b>Workspace</b></td><td>One client or brand (for example “Acme Foods”). Every workspace has its own strategy, calendar, notes and client link.</td></tr>
  <tr><td><b>Tab</b></td><td>A section inside a workspace: <i>Strategy</i>, <i>Campaign Grid</i>, a month such as <i>May 2026</i>, <i>Reels</i> or <i>Podcasts</i>.</td></tr>
  <tr><td><b>Item</b></td><td>A single post, reel or episode with a caption, platforms, time, status and notes.</td></tr>
</table>

<h2 id="quickstart">2. Quick start (10 minutes)</h2>
<ol>
  <li><b>Add your first client</b> — open <b>Client portal → Add client</b>. Enter the client name, logo and the contact's name and email. The client gets a login and sees their own portal.</li>
  <li><b>Write the Strategy</b> — open the <i>Strategy</i> tab and press <b>Start the strategy</b> (see section 4).</li>
  <li><b>Plan the content</b> — open a tab (or add a month with the <b>+</b> at the end of the tabs) and press <b>Add row</b>. Click <b>Edit</b> on a card to fill in the caption, platforms and time.</li>
  <li><b>Move work forward</b> — click the status pill on a card (To do → Ready → Done → Live). “Ready” means it is waiting for client approval.</li>
  <li><b>Share with the client</b> — press <b>Share</b> and send the private link. The client can see progress and leave comments, with no login.</li>
  <li><b>Create a report</b> — open <b>Documents</b>, choose a template and download a PDF, Word or CSV file.</li>
</ol>

<h2 id="workspaces">3. Workspaces</h2>
<ul>
  <li><b>Create:</b> Client portal → <b>Add client</b>. Your plan allows a set number of clients; if you reach it you will see a message to contact the platform admin.</li>
  <li><b>Rename or delete:</b> open the workspace, then the <b>⋯</b> menu in the top bar. Only the agency <b>owner</b> can delete a workspace.</li>
  <li><b>Master docs:</b> use <b>Add link</b> under the workspace title to pin links to the brand guide, shared drive or brief. They appear for the client too.</li>
  <li><b>Search:</b> press <b>Ctrl&nbsp;K</b> (or <b>/</b>) to jump to any tab, workspace or post.</li>
</ul>

<h2 id="strategy">4. Writing the Strategy</h2>
<p>The Strategy tab is the <b>brief every post must follow</b>. It is the first thing a new team member or client reads, so keep it short and specific.</p>
<p><b>To add or change it:</b> open the workspace → <i>Strategy</i> tab → press <b>Start the strategy</b> (when it is empty) or <b>Edit</b>. You can also press the small <b>+ Add</b> button next to any heading on the page (Brand voice, Content pillars, the campaign arc, Content mix) to jump straight to that part.</p>
<p>The editor shows five parts. In each one, press the <b>Add</b> button to add a new line, fill it in, and use the <b>×</b> to remove a line. Pressing <b>Enter</b> inside a Do / Don't line starts the next line. Blank lines are ignored. Press <b>Save strategy</b> when you are done.</p>

<table class="g">
  <tr><th style="width:22%">Part</th><th style="width:34%">What to write</th><th>What each line contains</th></tr>
  <tr><td><b>Do</b></td><td>Rules the content should follow.</td><td>One short rule per line.</td></tr>
  <tr><td><b>Don't</b></td><td>Things to avoid.</td><td>One short rule per line.</td></tr>
  <tr><td><b>Content pillars</b></td><td>The 3–6 themes all posts come from.</td><td>A <b>name</b> and a short <b>description</b>.</td></tr>
  <tr><td><b>Campaign arc</b></td><td>How the story builds over the weeks.</td><td>A title for the arc, then for each week: the <b>week</b> label, a <b>title</b> and a <b>description</b>.</td></tr>
  <tr><td><b>Content mix</b></td><td>How much of each content type you will make.</td><td>A <b>label</b>, an optional <b>note</b> and a <b>number</b>.</td></tr>
</table>

<h3>Example</h3>
<p class="muted">The editor has a <b>Fill with example</b> button that loads this for you, so you can see how it looks and edit it.</p>
<table class="g">
  <tr><th style="width:22%">Part</th><th>Example lines</th></tr>
  <tr><td><b>Do</b></td><td>Precise, measured, editorial · One idea per post — no hedging · Name the problem before the solution</td></tr>
  <tr><td><b>Don't</b></td><td>Motivational filler · Hustle praise or grind culture · Passive voice or corporate hedging</td></tr>
  <tr><td><b>Content pillars</b></td><td><b>Dependency</b> — The founder in every decision<br><b>Structure</b> — Workarounds become the process<br><b>Clarity</b> — The hidden cost of unclear briefs</td></tr>
  <tr><td><b>Campaign arc</b></td><td>Title: <i>Campaign arc — May to June</i><br><b>Week 1</b> · Hook &amp; provocation · Name the problem, no solution yet<br><b>Week 2</b> · Insight &amp; reframe · Reveal the real cause</td></tr>
  <tr><td><b>Content mix</b></td><td>Campaign posts · May + June · <b>10</b><br>Casual reels · Bridge week · <b>5</b></td></tr>
</table>

<div class="tip"><b>Tips.</b> Write rules as actions (“Name the problem first”), not wishes (“Be engaging”). Keep pillars to six or fewer. Use whole numbers in the content mix. You can edit the strategy at any time; clients see the update straight away.</div>
<p class="muted">Each part appears as its own block on the Strategy tab, and the same content can be added to a report with the <b>Strategy brief</b> section in Documents.</p>

<h2 id="content">5. Planning and tracking content</h2>
<h3>Tab types</h3>
<table class="g">
  <tr><th style="width:26%">Type</th><th>Best for</th></tr>
  <tr><td><b>Campaign Grid</b></td><td>Three columns per row, for example Chaos / System / What probably happened. Adding a row creates three cards at once.</td></tr>
  <tr><td><b>Posts</b></td><td>A month or campaign of single posts, each with a hook label, caption, visual direction, platforms and time.</td></tr>
  <tr><td><b>Reels</b></td><td>Short-form video with script, delivery note, visual direction and end card.</td></tr>
  <tr><td><b>Podcasts</b></td><td>Episodes with a title, hook, duration and pillar.</td></tr>
</table>
<p>Add one with the <b>+</b> at the end of the tab bar and choose the layout.</p>

<h3>Working with a card</h3>
<ul>
  <li><b>Edit</b> — change any field. <b>Upload image</b> — click the grey image area.</li>
  <li><b>Status</b> — click the pill. <b>To do</b> → <b>Ready</b> (needs approval) → <b>Done</b> → <b>Live</b> (published).</li>
  <li><b>Notes</b> — type in the box at the bottom of a card; it saves when you click away. Clients can write here too.</li>
  <li><b>Lock</b> — protects a finished card from edits and deletion until you unlock it.</li>
  <li><b>Board view</b> — switch the view toggle to see columns by status and <b>drag cards</b> between them.</li>
  <li><b>Filters</b> — the All / To do / Ready / Done / Live buttons show only that status. Long lists load nine at a time.</li>
</ul>

<h2 id="sharing">6. Sharing with clients and others</h2>
<p>Press <b>Share</b> on a workspace. There are two ways to give access:</p>
<table class="g">
  <tr><th style="width:26%">Method</th><th>How it works</th></tr>
  <tr><td><b>Client link</b></td><td>A private web address. No login. Choose <i>view &amp; comment</i> or <i>view only</i>. You can switch it off or regenerate it (the old link stops working immediately).</td></tr>
  <tr><td><b>People</b></td><td>Invite someone by name and email. They get their own client login and see only their own portal: they can view, comment and approve. The temporary password is shown once — send it to them securely.</td></tr>
</table>
<h3>Who can do what</h3>
<table class="g">
  <tr><th>Person</th><th>See</th><th>Comment</th><th>Edit content</th><th>Manage agency</th></tr>
  <tr><td>Owner</td><td>Yes</td><td>Yes</td><td>Yes</td><td>Yes</td></tr>
  <tr><td>Team member</td><td>Yes</td><td>Yes</td><td>Yes</td><td>No</td></tr>
  <tr><td>Guest — can edit</td><td>One workspace</td><td>Yes</td><td>Yes</td><td>No</td></tr>
  <tr><td>Guest — can comment</td><td>One workspace</td><td>Yes</td><td>No</td><td>No</td></tr>
  <tr><td>Client link</td><td>One workspace</td><td>If allowed</td><td>No</td><td>No</td></tr>
</table>

<h2 id="documents">7. Documents and templates</h2>
<ul>
  <li>Open <b>Documents</b>, pick a <b>template</b>, tick the tabs to include, optionally filter by status, and check the <b>live preview</b>. Then download <b>PDF</b>, <b>Word</b> or <b>CSV</b>.</li>
  <li>Built-in templates: <i>Campaign report</i>, <i>Content calendar</i>, <i>Client approval sheet</i>, <i>Strategy brief</i> and <i>Full campaign pack</i>.</li>
  <li>To make your own, go to <b>Settings → Document templates</b>. Choose a style — <b>Normal</b> (tables), <b>Graph</b> (tables plus charts) or <b>Detailed</b> (full cards) — then pick sections, page size, header and footer. The preview updates as you change options.</li>
  <li>A new, empty workspace previews with <b>sample data</b> so you can judge the layout before real content exists.</li>
</ul>

<h2 id="team">8. Team, roles and settings</h2>
<ul>
  <li><b>Team</b> (sidebar → Team) — everyone in your agency, their role and when they joined. People with the <i>Manage team</i> permission can <b>add</b>, <b>edit</b> (name, email, password, role) and <b>remove</b> members. Anyone can edit their own name, email and password. Your plan limits how many seats you have.</li>
  <li><b>Roles &amp; permissions</b> (sidebar → Roles &amp; permissions) — a role is a named set of permissions. Four are built in: <b>Owner</b> (everything, locked), <b>Manager</b> (runs every workspace but not team, roles or agency settings), <b>Editor</b> (creates and edits content, downloads documents) and <b>Viewer</b> (reads and comments only). You can <b>create your own roles</b>, tick exactly what they may do, edit them later, and delete a custom role once nobody uses it.</li>
  <li><b>What a permission controls:</b> creating, renaming and deleting workspaces; editing content and the strategy; sharing a workspace; generating documents; editing templates; managing the team; managing roles; and editing the agency profile.</li>
  <li><b>Settings → Agency profile</b> — your agency name and brand colour, used across the app, client links and documents.</li>
  <li><b>Team notes</b> — the bell icon opens one conversation per workspace between your team and the client.</li>
</ul>

<h2 id="faq">9. Questions and answers</h2>
<p><b>Why can't I add a workspace or team member?</b> Your agency has reached its limit. Ask the platform administrator to raise it.</p>
<p><b>A card won't save or delete.</b> It may be locked — click the lock icon to unlock it first.</p>
<p><b>The client can't open their link.</b> Check that link sharing is switched on under Share, and that you sent the newest link if you regenerated it.</p>
<p><b>I can't find where to add a pillar or week.</b> Open the Strategy tab and press <b>+ Add</b> next to the heading, or <b>Edit</b>, then use the <b>Add a pillar</b> / <b>Add a week</b> button inside the editor.</p>
<p><b>Can I get the whole thing as a file?</b> Yes — download this guide as a PDF from the Help page, and campaign reports from Documents.</p>
