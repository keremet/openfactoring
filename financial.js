function getDigit(f, c, i){
	if((c>='0')&&(c<='9')){
		return f*10 + parseInt(c)
	}
	throw new Error('Недопустимый символ "'+c +'" в позиции '+i)
}

function getFinancial(s,arr_err){
	if(s==''){
		arr_err.push('Пустая строка')
		return 0
	}
	var f=0
	try{	
		for(var i=0;i < s.length;i++){
			var c = s.charAt(i)
			if(c == "'")
				continue;
			if(c == '.'){
				if(++i<s.length){
					f = getDigit(f, s.charAt(i), i)
					if(++i<s.length){
						f = getDigit(f, s.charAt(i), i)
						return f;
					}
					return f*10;
				}
			}else
				f = getDigit(f, c, i)
		}
	}catch(e){
		arr_err.push(e.message);
		return 0;
	}	
	return f*100;
}

function financial2str(f){
	var s='';
	var t=f;
	for(var i=0;(i<3)||(t>0);i++){
		if(i==2){
			s='.'+s;
		}else
			if((i-2)%3==0){
				s="'"+s;
			}
		s=(t%10)+s;
		t=t/10|0
	}
	return s;
}

function proverka_fin(input) {
	input.value = input.value.replace(/[^\d.']/g, '');
};	
