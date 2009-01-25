{include file="file:$core_tpl/tickets/submenu.tpl.php"}

<table cellspacing="0" cellpadding="0" border="0" width="100%" style="padding-bottom:5px;">
<tr>
	<td width="1%" nowrap="nowrap" valign="top" style="padding-right:5px;">
		<h1>{$translate->_('mail.overview')}</h1>
	</td>
	<td width="98%" valign="middle">
	</td>
	<td width="1%" valign="middle" nowrap="nowrap">
		{include file="file:$core_tpl/tickets/quick_search_box.tpl.php"}
	</td>
</tr>
</table>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tbody>
    <tr>
      <td width="0%" nowrap="nowrap" valign="top">
      	<div id="overviewTotals">
      		{include file="file:$core_tpl/tickets/overview/sidebar.tpl.php"}
		</div>			
      </td>
      <td nowrap="nowrap" width="0%"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/spacer.gif{/devblocks_url}" width="5" height="1"></td>
      <td width="100%" valign="top">
	      {foreach from=$views item=view name=views}
	      	<div id="view{$view->id}">
		      	{$view->render()}
		    </div>
	      {/foreach}
	      
	      {include file="file:$core_tpl/tickets/whos_online.tpl.php"}
      </td>
      
    </tr>
  </tbody>
</table>

<script type="text/javascript">
{literal}
CreateKeyHandler(function doShortcuts(e) {

	var mykey = getKeyboardKey(e);
	
	switch(mykey) {
		case "a":  // list all
		case "A":
			try {
				document.getElementById('btnOverviewListAll').click();
			} catch(e){}
			break;
		case "b":  // bulk update
		case "B":
			try {
				document.getElementById('btnoverview_allBulkUpdate').click();
			} catch(e){}
			break;
		case "c":  // close
		case "C":
			try {
				document.getElementById('btnoverview_allClose').click();
			} catch(e){}
			break;
		case "e":  // expand all
		case "E":
			try {
				document.getElementById('btnOverviewExpand').click();
			} catch(e){}
			break;
		case "m":  // my tickets
		case "M":
			try {
				document.getElementById('btnMyTickets').click();
			} catch(e){}
			break;
		case "s":  // spam
		case "S":
			try {
				document.getElementById('btnoverview_allSpam').click();
			} catch(e){}
			break;
		case "t":  // take
		case "T":
			try {
				document.getElementById('btnoverview_allTake').click();
			} catch(e){}
			break;
		case "u":  // surrender
		case "U":
			try {
				document.getElementById('btnoverview_allSurrender').click();
			} catch(e){}
			break;
		case "x":  // delete
		case "X":
			try {
				document.getElementById('btnoverview_allDelete').click();
			} catch(e){}
			break;
	}
});
{/literal}
</script>
