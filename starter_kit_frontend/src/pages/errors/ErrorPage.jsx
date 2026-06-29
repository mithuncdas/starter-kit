import { Link, useNavigate } from "react-router-dom"
import { HugeiconsIcon } from "@hugeicons/react"
import { ArrowLeft01Icon, Home01Icon } from "@hugeicons/core-free-icons"
import { Button } from "@/components/ui/button"
import { ROUTES } from "@/routes/path"

/**
 * Shared layout for every HTTP error page.
 * Centers a large status code, a title and description, plus
 * "go back" and "go home" actions.
 */
export default function ErrorPage({ code, title, description, icon }) {
  const navigate = useNavigate()

  return (
    <div className="flex min-h-svh flex-col items-center justify-center bg-muted/30 px-4 py-10 text-center">
      <div className="grid w-full max-w-md gap-4">
        {icon ? (
          <div className="mx-auto flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
            <HugeiconsIcon icon={icon} className="size-6" />
          </div>
        ) : null}

        <p className="text-6xl font-bold tracking-tight text-primary">{code}</p>

        <div className="grid gap-1">
          <h1 className="text-xl font-semibold">{title}</h1>
          <p className="text-sm text-muted-foreground">{description}</p>
        </div>

        <div className="mt-2 flex items-center justify-center gap-2">
          <Button variant="outline" onClick={() => navigate(-1)}>
            <HugeiconsIcon icon={ArrowLeft01Icon} />
            Go back
          </Button>
          <Button asChild>
            <Link to={ROUTES.dashboard}>
              <HugeiconsIcon icon={Home01Icon} />
              Back home
            </Link>
          </Button>
        </div>
      </div>
    </div>
  )
}
