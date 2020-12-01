<div class="container-fluid wlrle">
    <div class="alerts"></div>
    <div class="btn-toolbar mb-1" role="toolbar">
        <div class="btn-group mr-2" role="group">
            <button type="button" class="btn btn-lg btn-light" title="Copy" data-request="copy">
                <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAy0lEQVRIS82V0RHCMAxDXydgBGASOgpMVkZhE+gG3QDOvYYLISEKaQ76W9myZNXtaPx0jfujEPTAAOzEYW7ACbgYXiGwgq3Y3MGsZq8S3AubO/g8vKKgOUFOgO3mDBwCoKwgR2DvjeSaI6hKCxBa+aagKi0KQdUy/5IgFeGo198oaE6gxNP/WH1lo7td/pRrLdmaH2PHLuVtqETFzXWfFJTuIGrpmhb9nmACNmp0FtwzLak63yI7dnZ21b/XS1oUgsLhNbjyR9M6JVAPxH0sGR7UMH0AAAAASUVORK5CYII="/>
            </button>
            <button type="button" class="btn btn-lg btn-light" title="Cut" data-request="cut">
                <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAABfklEQVRIS8WVPy9EURDFf/sJqLQ6LT4BCj2JToFEpUFCIQorUSipFCJUWhqNBuEDEJ1KotIpdeQkc2XcvW/fLF5M8pK39745Z8782xYNW6thfP6FYApYBsaBa2AfOP+p0lyBwM8KYNM1JOvAGjBgvhvArt5zgntgGNgG2vZsATemqCRkB9h0F0vAQfqdE3zYhT8vnSX/ReDQgc8Cpz6KKgWKPqmQggdgJAt/Eri0s3dgBrjIJUZroEKvOOch4NZy/mrgd6X8ldpUhRbYmEUuP9VlATgxECmUsicDf6zqssgcKDVq1z5gD1h1JEfAS7cWjhDI35NIhdSELErQD1y5QotESt7qWCIEHlzdpHrINDMTdSRVRU6r4tkAlCKBa32oCY6jJNE2TeApJfOORGdSIkUdFl0Vch7NvEMkv10ValulUyYl6q5vmze6KrotO3XUnFPnB7Jjm1ata+VYw1Zlmnw9g/bBF0ndqlDkSsOf/eHUzU3P95FB6xnUOzRO8AnP6lYZ5nszmgAAAABJRU5ErkJggg=="/>
            </button>
            <button type="button" class="btn btn-lg btn-light" title="Paste" data-request="paste" {{ $paste == 0 ? 'disabled' : '' }}>
                <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAA00lEQVRIS+2VMRYBQQyGvz2BI6CjUuscQ0nnVpSOQadVUTqCG/DimX3PmEiydium3eT//mSSnYqOT9WxPhZgBqyBgWLkAiyBnWbUAohA36hSYoZegOZYM3LLhN8qyhM1x16A8F4qyhNzR01noNb9HcAeWDz7G2qbt0UyhnJ54eMFpLgJsAVGH0hnYA4cJSYKOBniiSuQcRNAZIwf5qMV/AFoLUit/LpFV6BXGMHWAPK73hTegNYA1qZ69+AATEtjagE8myziK22TLUD4u/UmhwXzhDtKvi4Z6y6UXQAAAABJRU5ErkJggg=="/>
            </button>
        </div>
        <div class="btn-group mr-2" role="group">
            <button type="button" class="btn btn-lg btn-light" title="Select all" data-request="selectall">
                <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAABEUlEQVRIS82V4REBMRCFPxXQASqgBCpABeiACuiADlABHdABJdABFTDPJDOZILnEmeHXudm8b/fdu70KP/5VfqxPCNABVkAjsYkzMAYOOhcCqLCeKG7LdbYZA9wzxe2xZ/OhCf4GcDN+97yJS5tgAOyAOTBzIC8Aa4m1rYhFU2AJ1ICjl7gsgOyomi43wMhcn4BWzKLYBMq2hJRvxbBtBNfA8E3ikia4Al0DkLAAujcBFh/inASQhgvR/z6wDbwryQAXouu9ebifGFkAC9E0sR2VDSi6QaIAdWkjWVTU1l3shO4u8mOqda0Ipm5Uiev9eFnXPiC167f1oQlKB+R4Hv3kugU5nicBSrHEF4l28C31AZgIPRlzQq99AAAAAElFTkSuQmCC"/>
            </button>
        </div>
        <div class="btn-group mr-2" role="group">
            <button type="button" class="btn btn-lg btn-light" title="Delete selected" data-request="delete" disabled>
                <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAA9UlEQVRIS9WV4RHBQBCFPxXQATqgAypBB1pRAZ3QASXQARUwbyZrblYu7i6TGPmXy937dt9ubgd0/Aw61qd3wALYA5PCzK7ABjjZeZ+BNowLxe2YNKYxwLOl+Ieuz+BngAcwdNnVrRVloKJdqsIZROJqiFnVFN7ZtzPfLLoDywogMesOiQuqtSMwcoRkgM55iNaaxPU9C+Aheo9FXlQDHTLPFbkBZJcvfBEgFFfkoUUxSJZFc+e5AGHhzzU/ZxbgAOxct1jht8CqLaDk9sjK4L8A8jbWeqmZ3MJ54q8KXQEqaulMkPi6aeCkRpm8r/eZnBxZ6sYXQ2M5GXlMmTMAAAAASUVORK5CYII="/>
            </button>
            <button type="button" class="btn btn-lg btn-light" title="Edit" data-request="edit" disabled>
                <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAABAUlEQVRIS+WVuw3CMBBAXyZASPQwAqKhZQAqJoCGKZCAghGoYQh6KKFBjAAVLT0F6KQ4OjlxYsekIl2k83u+T3IJDT9Jw3x+KVgBb2CjL20LRsAO6FVkNgP2Kkbgy/R9oSW24A50A+FD4AB01LlMYgs+aZBv6QbAFbAla0CyyvUgRCCAOTC2JFsDjxHomj8tyaWsyT4ZaLhhiWQCnO3+hfagCC7MrOYxAl94H7gZkW8GIfAj0A4R+MKF+QJaejp9MjCN1+V11Tw3JHUEzoYC0YIyuGRYSxDyR/8DgRmzkLLYsQ+9T4oWjiySqp3guoDAp8DJ9aHF3LzwrO9iqS1uXPAFenpCGdgqgFUAAAAASUVORK5CYII="/>
            </button>
        </div>
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-lg btn-light" title="New folder" data-request="createdirectory">
                <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAsElEQVRIS9WV4Q2CQAxGHxM4grKJbKKT6SiMwAiwARtoaoJp4IC2SUngL73v9bte24rkr0rW51DAHXgBt5mrDmiAMeJWO+iB64pIGKIBn0iGhTOS6BNo5V8GQHQFUmcC/tpZDsKAKSFLvX6xXgfnBOx1+9p1ma8oHaB76Zw1ONSBZ1yZi+wRXbjVL0Tm/SWqNjs3THtFA2ThvDd2gpUt4o/SuLYKuOL2msglVgpOB3wBY3AkGbEXTQQAAAAASUVORK5CYII="/>
            </button>
            <button type="button" class="btn btn-lg btn-light" title="Upload files" data-request="uploadfile">
                <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAuElEQVRIS+WU0Q3CMAxEXyeADYAJgA3YBNiMUboBbIDYgA1AJxmpVDScFVUCNT+pEvuefUrdMPJqRtZneoBDWHpyrc1YtAHOIbwFLg7EBcyBK6Bd6w6sYi9yXICqXfeUdKZOqgHyez+gortjieB28NJ4xIedZweGcBXASU7HdDtIJw94/6YzPcC3Z6/7Kot+G6D5MnNKNGJuwFJx3Ve0A/TrLwyBUojENdbbPqBS93N6dlSki/h/wBOyfCMZVuA+MgAAAABJRU5ErkJggg=="/>
            </button>
        </div>
    </div>
    <div class="btn-toolbar" role="toolbar">
        <div class="btn-group mr-2" role="group">
            <button type="button" class="btn btn-lg btn-light" title="Back" data-request="back" data-id="{{ $back }}" {{ $back == 0 ? 'disabled' : '' }}>
                <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAu0lEQVRIS8WV4Q2CMBBGHxM4ArqJmyCTqZs4im7gBpgvoaY50rQcLfQv3Hv9epTraLy6xnx2F1yBO3B2JnsDI/AK9TaBXuid8FAmxiUlmDbCF1yb4HDBExgKUv43viaB4DegJOVqQYBr89UFMby6wMJTx29TFR/RY744ub66BQJbSfUeWEkTQSxpJggS3YXcKm5yDpR6fpzgC5y8257rPvE8sf8iDRx9lt6ZILh6lBw4Gze/LN99JldP8APn5CoZPerbGgAAAABJRU5ErkJggg=="/>
            </button>
            <button type="button" class="btn btn-lg btn-light" title="Forward" data-request="forward" data-id="{{ $forward }}" {{ $forward == 0 ? 'disabled' : '' }}>
                <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAsElEQVRIS82V0Q3CMAxEXydgBMomjAKT0VE6CmzABkX3UVSljWvFGDXf8T1fbMcdyadL1ufvgCvwAPpGZ0/gDoxzfOlAF86N4nOYNC41wBQUX+mWDg4B0BurTtb5Jt7iQDG3HUgYoOwtyE8AFsQN8AzilpPjALY6ZemqVge3AwuQWuTUNk0fNM93FapBCPAGTh4F485ruU/KQdLCGQI7QeJqgOrCCSa/Dvd8BSFoOuAD980iGcUiwSkAAAAASUVORK5CYII="/>
            </button>
        </div>
        <div class="btn-group mr-2" role="group">
            <button type="button" class="btn btn-lg btn-light" title="Up" data-request="up" data-id="{{ $up }}" {{ $up == 0 ? 'disabled' : '' }}>
                <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAsklEQVRIS+2VwQ3CMAwArxMwAnQTRoHJgE06Ct2gGxRZSlCVEsdJSFEl8uknvrPjyu5ofLrGfDYXnIEbcCqs7AlcgcHHhxXIhWMh3IcJo48J5kr4ihtW8HPBw72v9Ek778RzKhD4xVHlq0myBUu4z1yTZAk+wVMSs0CDaxKzIGykD0z9bX8B+30i66gqbnK1YAIOVkrk3rjcJ+EskoVzr9gJApcREl04lcmvwzffyV+v4AW1cScZze7mswAAAABJRU5ErkJggg=="/>
            </button>
        </div>
        <div class="btn-group mr-2" role="group">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    @include('wlrle::bread')
                </ol>
            </nav>
        </div>
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-lg btn-light" title="Refresh" data-request="refresh">
                <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAABOElEQVRIS7WV0VFCQQxFDxVgCVoB2oFWoHaAFYgdSAdagZagFWgHQgVaglYgc2c2M2GHLNmdefkCXvaeJDdvmTFxzCbWZwSwBF6zhfUCJP4C+cJ6ACau4tPnsoleXIAN8Ak8Az+tcY0CTPMXeGh50gKo6hPgqaj5Lq6AFXBdnt1FkAhwCnwVwJkbQ22yfVcnF4fGFQFU9T3wDtxUM67X9K10Ij/U1V5EAJm4ADQKmdmKS+CjGK8uUoD/kpVdgjA/EpgcMDKiLXCeHZGZLANv3SHdQTLZh5m8Bh6zAK2pupgDfsc1OkH0m0J538Bf+ax1TZmsJP9iqUp1ZRvlIdbRwRv22JbosITVSR0eEq7xMYCNQSC9cHo3LMLrwdMyAJ9v65sS18ERQFp8BND1dzkCCM2MHvSOqBuwA9jZPxkXFEUTAAAAAElFTkSuQmCC"/>
            </button>
        </div>
    </div>
    <div class="row content">
        @include('wlrle::items')
    </div>
</div>