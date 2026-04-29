<h2>{$displayName}</h2>

{if $disallow}
  <p class="status error">This contact cannot log in and perform event registrations.</p>
{else}
  <p class="status success">This contact can perform event registrations when logged in.</p>
{/if}

<table>
  <thead>
    <th>Assessment</th>
    <th>Can register?</th>
  </thead>
  <tbody>    
  {foreach from=$results item=result}
    {capture assign="accessMarker"}
      {if $result.access}
        <span class="fpptaregdeny-status fpptaregdeny-status-grant"><i class="crm-i fa-check"></i></span>
      {else}
        <span class="fpptaregdeny-status fpptaregdeny-status-deny"><i class="crm-i fa-times"></i></span>
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