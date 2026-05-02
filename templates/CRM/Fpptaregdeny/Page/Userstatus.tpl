<h2> <a href="{crmURL p='civicrm/contact/view' q="action=view&reset=1&cid=`$cid`"}">{$displayName}</a></h2>

{if $disallow}
  <p class="status error">This contact cannot log in and perform event registrations. See below for disqualifying criteria.</p>
{else}
  <p class="status success">This contact can perform event registrations when logged in.</p>
{/if}
<br />
<br />
<table>
  <thead>
    <th>Assessment</th>
    <th>Can register?</th>
  </thead>
  <tbody>    
  {foreach from=$results item=result}
    {capture assign="accessMarker"}
      {if $result.access === TRUE}
        <span class="fpptaregdeny-status fpptaregdeny-status-grant"><i class="crm-i fa-check"></i></span>
      {elseif $result.access === FALSE}
        <span class="fpptaregdeny-status fpptaregdeny-status-deny"><i class="crm-i fa-times"></i></span>
      {else $result.access === FALSE}
        <span class="fpptaregdeny-status fpptaregdeny-status-null"><i class="crm-i fa-minus"></i></span>
      {/if}
    {/capture}
    <tr>
      <td>
        <h4>{$result.adminDescription}</h4>
        {$result.admin}
      </td>
      <td>{$accessMarker}</td>
    </tr>
  {/foreach}
  </tbody>
</table>