{extends file='common.tpl'}
{block name=body}
    <div class="container">
        <div class="card">
            {$title}
            <hr>
            <h3>{$exam.name} <small>@{$exam.time}</small></h3>
            <p>请输入相关信息以查询</p>
            <form id="query" name="query" method="post" action="/exam/{$exam.id}/query">
                <div class="form-group">
                    <label for="id">考号</label>
                    <input type="text" class="form-control" name="id" id="id" placeholder="请输入完整考号">
                </div>
                <div class="form-group">
                    <label for="name">姓名</label>
                    <input type="text" class="form-control" name="name" id="name" placeholder="王二">
                </div>
                <button type="submit" class="btn btn-info">查询</button>
            </form>
        </div>
    </div>
{/block}