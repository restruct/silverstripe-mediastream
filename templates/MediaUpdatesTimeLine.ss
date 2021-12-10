<ul id="TimeLineList" class="timeline-v1">
	<% if $MediaUpdates %>
    <% loop $MediaUpdates %>
    <li class="$EvenOdd <% if $Even %>timeline-inverted<% end_if %>">
<%--        <div class="timeline-badge primary">--%>
<%--            <i class="fas fa-record-vinyl"></i>--%>
<%--        </div>--%>
        <div class="timeline-panel $Type $Content.ATT">

<%--            <% if $ImageURL && $MediaType!= 'Video' %>--%>
<%--                <div class="timeline-heading">--%>
<%--                    <img class="img-fluid" src="$ImageURL" alt=""/>--%>
<%--                </div>--%>
<%--            <% end_if %>--%>

            <% if $Image && $MediaType!= 'Video' %>
                <div class="timeline-heading">
                    <img class="img-fluid" src="$Image.URL" alt=""/>
                </div>
            <% end_if %>

            <div class="timeline-body">
				<% if $Title %><h4 class="timeline-title">
					<% if $OriginURL %><a href="{$OriginURL}"><% end_if %>
						{$Title}
				<% if $OriginURL %></a><% end_if %>
				</h4><% end_if %>
				$Content
            </div>

            <div class="timeline-footer">
                <div class="timeline-navigation row">
                    <div class="col-auto">
						{$TimeStamp.Nice}
                        (<small><a href="{$OriginURL}" target="_blank">@{$Type}</a></small>)
                    </div>

                    <div class="col">
                        <div class="post-linking text-end">
                            <a href="{$Top.PageById(901).Link}">
                                Meer <i class="fas fa-forward"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </li>
    <% end_loop %>
    <% end_if %>
    <li class="clearfix" style="float: none;"></li>
</ul>
