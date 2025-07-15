document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('searchAddressBtn').addEventListener('click', function () {
        const zip = document.getElementById('postal_code').value;

        if (!zip) {
            alert('郵便番号を入力してください');
            return;
        }

        fetch('Searchaddress.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'postal_code=' + encodeURIComponent(zip)
        })
            .then(response => response.json())
            .then(data => {
                console.log('検索結果:', data);  // ★ここ追加

                if (data && data.prefecture) {
                    document.getElementById('prefecture').value = data.prefecture;
                    document.getElementById('city_town').value = data.city_town;
                } else {
                    alert('該当する住所が見つかりません');
                }
            })
            //             .then(response => response.json())
            //             .then(data => {
            //                 if (data && data.prefecture) {
            //                     document.getElementById('prefecture').value = data.prefecture;
            //                     document.getElementById('city_town').value = data.city_town;
            //                 } else {
            //                     alert('該当する住所が見つかりません');
            //                 }
            //             })
            .catch(error => {
                console.error('検索エラー:', error);
                alert('検索に失敗しました');
            });
    });
});
