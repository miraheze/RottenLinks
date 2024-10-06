local p = {}

function p.setupInterface(arguments)
	p.setupInterface = nil

	local php = mw_interface
	mw_interface = nil

	mw = mw or {}
	mw.ext = mw.ext or {}

	p.getStatus = php.getStatus

	mw.ext.rottenLinks = p
	package.loaded['mw.ext.rottenLinks'] = p
end

return p
