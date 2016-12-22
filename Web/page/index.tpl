{extends file='common.tpl'}
{block name=body}
    <div class="container">
        {$title}
        <hr>
        <div class="alert alert-warning alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <strong>注意！</strong>此系统正处于内测阶段，开放时间不定，随时可能停站维护。
        </div>
        <div class="jumbotron">
            <p>欢迎使用本系统。本系统是由非官方组织维护的。</p>
            <p>本系统的功能与声明如下：</p>
            <p>· 方便快捷地查询非选择题分数（如填空题、大题）。</p>
            <p>· 我们未对数据做任何修改，分数为网阅原始评分。如果在教务处修改过成绩，不会影响在这里的显示。</p>
            <p>· 本系统未对学校网络、服务器造成任何破坏/修改，拒绝追责。</p>
        </div>
        <div class="card">
            <h3>请选择考试场次</h3>
            <div class="table-responsive">
                <table class="table table-hover">
                    <tbody>
                    <tr>
                        <th>考试时间</th>
                        <th>考试名称</th>
                    </tr>
                    {foreach $exams as $exam}
                        <tr>
                            <td>{$exam.time}</td>
                            <td><a href="/exam/{$exam.id}">{$exam.name}</a></td>
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
{/block}
